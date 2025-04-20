<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Retourne les statistiques pour le dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        try {
            // Nombre total de projets
            $totalProjets = Projet::count();
            
            // Nombre de projets par statut
            $projetsByStatus = Projet::select('statut', DB::raw('count(*) as total'))
                ->groupBy('statut')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->statut => $item->total];
                })
                ->toArray();
                
            // S'assurer que tous les statuts sont présents dans le tableau
            $allStatus = ['en cours', 'Validé', 'Rejeté'];
            foreach ($allStatus as $status) {
                if (!isset($projetsByStatus[$status])) {
                    $projetsByStatus[$status] = 0;
                }
            }
            
            // Nombre d'utilisateurs uniques (basé sur l'email)
            $uniqueUsers = Projet::distinct('email')->count('email');
            
            // Projets par type de projet
            $projetsByType = Projet::select('type_projet', DB::raw('count(*) as total'))
                ->groupBy('type_projet')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type_projet => $item->total];
                })
                ->toArray();
                
            // Projets par forme juridique
            $projetsByForme = Projet::select('forme_juridique', DB::raw('count(*) as total'))
                ->groupBy('forme_juridique')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->forme_juridique => $item->total];
                })
                ->toArray();
                
            // Statistiques de validation mensuelle (PostgreSQL)
            $projetsByMonth = Projet::select(
                    DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                    DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => intval($item->year) . '-' . str_pad(intval($item->month), 2, '0', STR_PAD_LEFT),
                        'total' => $item->total
                    ];
                })
                ->toArray();
                
            // Les 5 derniers projets soumis
            $recentProjets = Projet::select('id', 'nom', 'prenoms', 'email', 'type_projet', 'forme_juridique', 'statut', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
                
            // Taux de conversion (projets validés / total)
            $conversionRate = $totalProjets > 0 
                ? ($projetsByStatus['Validé'] ?? 0) / $totalProjets * 100 
                : 0;
                
            // Construire la réponse JSON
            return response()->json([
                'success' => true,
                'data' => [
                    'total_projets' => $totalProjets,
                    'projets_par_statut' => $projetsByStatus,
                    'utilisateurs_uniques' => $uniqueUsers,
                    'projets_par_type' => $projetsByType,
                    'projets_par_forme_juridique' => $projetsByForme,
                    'evolution_mensuelle' => $projetsByMonth,
                    'projets_recents' => $recentProjets,
                    'taux_conversion' => round($conversionRate, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
