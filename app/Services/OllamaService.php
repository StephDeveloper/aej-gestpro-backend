<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class OllamaService 
{
    protected $baseUrl;
    protected $model;

    public function __construct()
    {
        $this->baseUrl = env('OLLAMA_API_URL', 'http://localhost:11434/api/generate');
        $this->model = env('OLLAMA_MODEL', 'llama3.2:latest');
    }
    
    /**
     * Extraire le texte d'un fichier PDF
     *
     * @param string $pdfPath Chemin du fichier PDF dans le storage
     * @return string Texte extrait du PDF
     */
    public function extractPdfText(string $pdfPath): string
    {
        try {
            // Récupérer le chemin complet du fichier
            $fullPath = storage_path('app/public/' . $pdfPath);
            
            // Vérifier que le fichier existe
            if (!file_exists($fullPath)) {
                Log::error("Le fichier PDF n'existe pas: " . $fullPath);
                return "Fichier PDF introuvable.";
            }
            
            // Parser le PDF en utilisant PDFParser
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($fullPath);
                $text = $pdf->getText();
                
                // Tronquer le texte si trop long (Ollama a des limites de contexte)
                if (strlen($text) > 15000) {
                    $text = substr($text, 0, 15000) . "...";
                }
                
                return $text;
            } catch (\Exception $parserException) {
                Log::error('Erreur de PDFParser: ' . $parserException->getMessage());
                
                // Méthode alternative pour extraire le texte
                if (function_exists('shell_exec')) {
                    $content = shell_exec('pdftotext ' . escapeshellarg($fullPath) . ' -');
                    if ($content !== null) {
                        return $content;
                    }
                }
                
                // Si tout échoue, retourner un texte générique pour le test
                return "Ce document est un plan d'affaires pour un projet en Côte d'Ivoire. Veuillez analyser ce projet comme s'il s'agissait d'un plan d'affaires standard pour le marché ivoirien.";
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'extraction du texte PDF: ' . $e->getMessage());
            return "Impossible d'extraire le texte du PDF. Erreur: " . $e->getMessage();
        }
    }
    
    /**
     * Analyser un texte avec Ollama pour obtenir des recommandations et une notation
     *
     * @param string $text Texte du plan d'affaires
     * @return array Tableau contenant la notation et les recommandations
     */
    public function analyzeBusinessPlan(string $text): array
    {
        try {
            $transformedText = $this->transformText($text, 1000);
            
            $prompt = "Tu es un expert en analyse de plans d'affaires en Cote d'Ivoire avec une connaissance approfondie du marché local ivoirien. Analyse ce plan d'affaires {$transformedText} et évalue-le selon les critères suivants avec une note sur 100 pour chaque critère.

CRITÈRES D'ÉVALUATION (chaque critère doit être noté sévèrement, entre 0 et 20):
1. Viabilité économique: Évalue si le modèle d'affaires est viable dans le contexte ivoirien (marges réalistes, prix adaptés au pouvoir d'achat local, coûts opérationnels réalistes)
2. Innovation et différenciation: Évalue si l'offre se démarque des concurrents existants sur le marché ivoirien
3. Conformité réglementaire: Vérifie si le projet respecte les lois et réglementations ivoiriennes (licences, autorisations, taxes)
4. Stratégie financière: Analyse la cohérence des projections financières, ROI, et sources de financement
5. Potentiel de croissance: Évalue les possibilités d'expansion et de scaling dans le contexte du marché ivoirien

Tu dois retourner un JSON valide avec les clés suivantes:
- note_globale: note moyenne sur 100 (sois très critique)
- criteres: un objet avec les notes pour chaque critère suivant: viabilite_economique, innovation, conformite_reglementaire, strategie_financiere, potentiel_croissance
- forces: un tableau de forces identifiées (maximum 3)
- faiblesses: un tableau de faiblesses identifiées (maximum 3)
- recommandations: une chaîne de recommandations détaillées adaptées au marché ivoirien, max 300 mots";
            
            // Vérifier si l'URL d'API est correcte et accessible
            $apiUrl = $this->baseUrl;
            
            // Log de l'URL utilisée pour le debugging
            Log::info("Tentative de connexion à l'API Ollama: " . $apiUrl);
            
            $response = Http::timeout(120) // 2 minutes = 120 secondes
                ->connectTimeout(60) // 1 minute = 60 secondes
                ->withOptions([
                    'curl' => [
                        CURLOPT_TCP_KEEPALIVE => 1,
                        CURLOPT_TCP_KEEPIDLE => 60,
                        CURLOPT_TCP_NODELAY => 1,
                    ]
                ])
                ->retry(3, 2000, function ($exception) {
                    Log::warning("Retry triggered: " . $exception->getMessage());
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                            $exception instanceof \Illuminate\Http\Client\RequestException;
                })
                ->post($apiUrl, [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'temperature' => 0.3,
                    'top_p' => 0.8,
                    'num_predict' => 100,
                    'context_window' => 2048, // Limiter la taille du contexte
                    'repeat_penalty' => 1.1
                ]);
            
            if (!$response->successful()) {
                Log::error('Erreur API Ollama: ' . $response->status() . ' - ' . $response->body());
                
                // Si l'erreur est 404, c'est probablement une URL incorrecte
                if ($response->status() == 404) {
                    // Essayer avec une URL alternative
                    $altUrl = str_replace('/api/generate', '/api/generate', $apiUrl);
                    Log::info("Tentative avec URL alternative: " . $altUrl);
                    
                    $response = Http::timeout(120)
                        ->connectTimeout(60)
                        ->retry(2, 1000)
                        ->post($altUrl, [
                            'model' => $this->model,
                            'messages' => [
                                ['role' => 'user', 'content' => $prompt]
                            ],
                            'format' => 'json',
                            'stream' => false
                        ]);
                    
                    if (!$response->successful()) {
                        throw new \Exception('API Ollama inaccessible: URL originale et alternative incorrectes');
                    }
                    
                    // Si on a réussi avec l'URL alternative, on a un format différent
                    $responseContent = $response->json();
                    if (isset($responseContent['message']['content'])) {
                        $jsonStr = $responseContent['message']['content'];
                        preg_match('/{.*}/s', $jsonStr, $jsonMatches);
                        
                        if (!empty($jsonMatches[0])) {
                            return json_decode($jsonMatches[0], true) ?: $this->getDefaultAnalysisResult('Erreur de décodage JSON');
                        }
                    }
                    
                    throw new \Exception('Format de réponse invalide depuis l\'API alternative');
                }
                
                return $this->getDefaultAnalysisResult("Erreur HTTP " . $response->status());
            }
            
            $responseData = $response->json();
            
            if (!isset($responseData['response'])) {
                throw new \Exception('Réponse invalide: clé "response" manquante');
            }
            
            // Extraire la partie JSON de la réponse
            preg_match('/{.*}/s', $responseData['response'], $matches);
            
            if (empty($matches[0])) {
                Log::warning('Format de réponse Ollama invalide: ' . $responseData['response']);
                
                // Tenter de nettoyer la réponse pour extraire un JSON valide
                $cleanedResponse = preg_replace('/```json|```/', '', $responseData['response']);
                $jsonData = json_decode(trim($cleanedResponse), true);
                
                if (!$jsonData) {
                    return $this->getDefaultAnalysisResult("Format de réponse invalide");
                }
                
                return $jsonData;
            }
            
            $analysisResult = json_decode($matches[0], true);
            
            if (!$analysisResult) {
                Log::error('Erreur de décodage JSON: ' . $matches[0]);
                return $this->getDefaultAnalysisResult("Erreur de décodage JSON");
            }
            
            return $analysisResult;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'analyse avec Ollama: ' . $e->getMessage());
            return $this->getDefaultAnalysisResult($e->getMessage());
        }
    }

    /**
     * Retourne un résultat d'analyse par défaut en cas d'erreur
     */
    private function getDefaultAnalysisResult(string $errorMessage): array
    {
        return [
            'note_globale' => 0,
            'criteres' => [
                'viabilite_economique' => 0,
                'innovation' => 0,
                'conformite_reglementaire' => 0,
                'strategie_financiere' => 0,
                'potentiel_croissance' => 0
            ],
            'forces' => ['Non évalué'],
            'faiblesses' => ['Non évalué'],
            'recommandations' => "Une erreur s'est produite lors de l'analyse du plan d'affaires avec l'IA: " . $errorMessage
        ];
    }

    private function transformText($texte, $longueurMax)
    {
        try {
            // Sauvegarder la longueur originale du texte
            $longueurOriginale = mb_strlen($texte);
            
            // Translittération des caractères accentués, mais de façon plus douce
            // On préserve les caractères non-latins (comme les caractères asiatiques)
            $texte = transliterator_transliterate('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove;', $texte);
            
            // Nettoyage plus sélectif des caractères problématiques
            $texte = str_replace(["\u2019", "\u2018", "\u201C", "\u201D"], ["'", "'", '"', '"'], $texte);
            
            // Nettoyage du texte sans être trop agressif
            $texte = preg_replace('/\s{2,}/', ' ', $texte); // Remplacer les espaces multiples par un seul
            $texte = str_replace(["\r\n\r\n", "\n\n"], [". ", ". "], $texte); // Convertir les paragraphes en phrases
            $texte = preg_replace('/[\r\n\t]+/', ' ', $texte); // Remplacer les retours à la ligne simples
            $texte = trim($texte);
            
            // Si le texte est déjà assez court, retourner tel quel
            if (mb_strlen($texte) <= $longueurMax) {
                return $texte;
            }
            
            // Pour les textes longs, sélectionner stratégiquement les parties importantes
            $resumeIntelligent = '';
            
            // Diviser en paragraphes, puis en phrases
            $paragraphes = preg_split('/\. /', $texte);
            
            // Si longueur maximale est très courte, sélectionner seulement le début et la fin
            if ($longueurMax < 1000) {
                // Prendre le premier paragraphe (introduction)
                if (count($paragraphes) > 0) {
                    $resumeIntelligent .= $paragraphes[0] . ". ";
                }
                
                // Prendre quelques informations du milieu
                if (count($paragraphes) > 5) {
                    $milieu = (int)(count($paragraphes) / 2);
                    $resumeIntelligent .= $paragraphes[$milieu] . ". ";
                }
                
                // Prendre la fin (conclusion)
                if (count($paragraphes) > 2) {
                    $resumeIntelligent .= $paragraphes[count($paragraphes) - 1] . ". ";
                }
                
                // Si le résumé est encore trop long, couper
                if (mb_strlen($resumeIntelligent) > $longueurMax) {
                    $resumeIntelligent = mb_substr($resumeIntelligent, 0, $longueurMax - 3) . "...";
                }
                
                return $resumeIntelligent;
            }
            
            // Pour les textes plus longs, utiliser un échantillonnage plus complet
            $nb_paragraphes_a_garder = ceil($longueurMax / 200); // Environ 200 caractères par paragraphe en moyenne
            
            if (count($paragraphes) <= $nb_paragraphes_a_garder) {
                // Si on a moins de paragraphes que nécessaire, on garde tout
                return $texte;
            }
            
            // Sélection des paragraphes clés
            $paragraphes_selectionnes = [];
            
            // Toujours garder le premier paragraphe (introduction)
            $paragraphes_selectionnes[] = $paragraphes[0];
            
            // Toujours garder le dernier paragraphe (conclusion)
            $paragraphes_selectionnes[] = $paragraphes[count($paragraphes) - 1];
            
            // Répartir le reste des paragraphes à garder uniformément dans le document
            $nb_restants = $nb_paragraphes_a_garder - 2;
            if ($nb_restants > 0) {
                $intervalle = (count($paragraphes) - 2) / ($nb_restants + 1);
                for ($i = 1; $i <= $nb_restants; $i++) {
                    $index = round($i * $intervalle);
                    if ($index > 0 && $index < count($paragraphes) - 1) {
                        $paragraphes_selectionnes[] = $paragraphes[$index];
                    }
                }
            }
            
            // Trier les indices pour préserver l'ordre original
            sort($paragraphes_selectionnes);
            
            // Combiner les paragraphes sélectionnés
            $resumeIntelligent = implode(". ", $paragraphes_selectionnes) . ".";
            
            // Si après tous ces efforts, le texte est encore trop long
            if (mb_strlen($resumeIntelligent) > $longueurMax) {
                $resumeIntelligent = mb_substr($resumeIntelligent, 0, $longueurMax - 3) . "...";
            }
            
            // Indiquer le taux de réduction
            $pourcentage = round((mb_strlen($resumeIntelligent) / $longueurOriginale) * 100);
            Log::info("Texte réduit de $longueurOriginale à " . mb_strlen($resumeIntelligent) . " caractères ($pourcentage%)");
            
            return $resumeIntelligent;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la transformation du texte: ' . $e->getMessage());
            return $texte;
        }
    }
}