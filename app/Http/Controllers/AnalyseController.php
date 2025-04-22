<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyseController extends Controller
{
    protected $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }
    
    /**
     * Récupérer le classement des plans d'affaires analysés.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function classementPlansAffaires()
    {
        try {
            // Récupérer tous les projets
            $projets = Projet::all();
            
            $resultats = [];
            $cacheTime = 60 * 24; // Cache pendant 24 heures
            
            foreach ($projets as $projet) {
                // Essayer de récupérer les résultats du cache
                $cacheKey = 'analyse_projet_' . $projet->id;
                
                if (Cache::has($cacheKey)) {
                    $analyse = Cache::get($cacheKey);
                } else {
                    // Extraire le texte du PDF
                    $texte = $this->ollamaService->extractPdfText($projet->plan_affaire);
                    
                    // Analyser le plan d'affaires avec Ollama
                    $analyse = $this->ollamaService->analyzeBusinessPlan($texte);
                    
                    // Mettre en cache les résultats
                    Cache::put($cacheKey, $analyse, $cacheTime);
                }
                
                $resultats[] = [
                    'projet' => [
                        'id' => $projet->id,
                        'nom' => $projet->nom,
                        'prenoms' => $projet->prenoms,
                        'email' => $projet->email,
                        'type_projet' => $projet->type_projet,
                        'forme_juridique' => $projet->forme_juridique,
                        'created_at' => $projet->created_at,
                    ],
                    'analyse' => $analyse
                ];
            }
            
            // Trier les résultats par note décroissante
            usort($resultats, function ($a, $b) {
                return $b['analyse']['note_globale'] <=> $a['analyse']['note_globale'];
            });
            
            // Ajouter le rang à chaque résultat
            $rang = 1;
            foreach ($resultats as &$resultat) {
                $resultat['rang'] = $rang++;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Classement des plans d\'affaires récupéré avec succès',
                'data' => $resultats
            ], 200);

            // echo json_encode($this->transformText($texte, 1000));

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du classement des plans d\'affaires: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération du classement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function transformText($texte, $longueurMax)
    {
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
    }
} 