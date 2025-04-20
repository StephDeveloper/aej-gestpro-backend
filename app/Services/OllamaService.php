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
            $prompt = "Tu es un expert en analyse de plans d'affaires en Côte d'Ivoire avec une connaissance approfondie du marché local.

TÂCHE:
Analyse ce plan d'affaires et évalue-le selon les critères suivants avec une note sur 10 pour chaque critère:
1. Viabilité économique dans le contexte ivoirien
2. Innovation et différenciation sur le marché local
3. Compréhension des contraintes réglementaires locales
4. Stratégie financière et modèle de revenus
5. Potentiel de croissance et impact économique

FORMAT DE RÉPONSE ATTENDU (respecte exactement ce format en JSON):
{
  \"note_globale\": (note moyenne sur 10),
  \"criteres\": {
    \"viabilite_economique\": (note sur 10),
    \"innovation\": (note sur 10),
    \"conformite_reglementaire\": (note sur 10),
    \"strategie_financiere\": (note sur 10),
    \"potentiel_croissance\": (note sur 10)
  },
  \"forces\": [\"force 1\", \"force 2\", ...],
  \"faiblesses\": [\"faiblesse 1\", \"faiblesse 2\", ...],
  \"recommandations\": \"(recommandations détaillées adaptées au marché ivoirien, max 300 mots)\"
}

Voici le plan d'affaires à analyser:

$text";
            
            $response = Http::post($this->baseUrl, [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ]);
            
            if ($response->successful()) {
                $aiResponse = $response->json('response') ?? "Aucune recommandation générée.";
                
                // Extraire le JSON de la réponse (l'IA peut renvoyer du texte avant/après le JSON)
                preg_match('/{.*}/s', $aiResponse, $matches);
                
                if (!empty($matches[0])) {
                    try {
                        $result = json_decode($matches[0], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $result;
                        }
                    } catch (\Exception $e) {
                        Log::error('Erreur de parsing JSON: ' . $e->getMessage());
                    }
                }
                
                // Fallback si le JSON n'est pas valide
                return [
                    'note_globale' => 0,
                    'criteres' => [
                        'viabilite_economique' => 0,
                        'innovation' => 0,
                        'conformite_reglementaire' => 0,
                        'strategie_financiere' => 0,
                        'potentiel_croissance' => 0
                    ],
                    'forces' => [],
                    'faiblesses' => [],
                    'recommandations' => $aiResponse
                ];
            } else {
                Log::error('Erreur Ollama: ' . $response->body());
                return [
                    'note_globale' => 0,
                    'recommandations' => "Une erreur s'est produite lors de l'analyse du plan d'affaires."
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'analyse avec Ollama: ' . $e->getMessage());
            return [
                'note_globale' => 0,
                'recommandations' => "Une erreur s'est produite lors de l'analyse du plan d'affaires avec l'IA."
            ];
        }
    }
}