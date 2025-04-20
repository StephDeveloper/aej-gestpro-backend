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
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du classement des plans d\'affaires: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération du classement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 