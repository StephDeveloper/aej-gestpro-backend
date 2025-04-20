<?php

namespace App\Http\Controllers;

use App\Mail\ProjetEnregistre;
use App\Models\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjetController extends Controller
{
    /**
     * Récupère la liste des projets avec les URLs complètes pour les fichiers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $projets = Projet::orderBy('created_at', 'desc')->get();
        
        // Ajouter les URLs complètes pour les fichiers
        foreach ($projets as $projet) {
            $projet->cni_url = Storage::url($projet->cni);
            $projet->piece_identite_url = Storage::url($projet->piece_identite);
            $projet->plan_affaire_url = Storage::url($projet->plan_affaire);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Liste des projets récupérée avec succès',
            'data' => $projets
        ], 200);
    }

    /**
     * Enregistre un nouveau projet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'type_projet' => 'required|string|max:255',
            'forme_juridique' => 'required|string|max:255',
            'num_cni' => 'required|string|max:255|unique:projets',
            'cni' => 'required|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:1024',
            'piece_identite' => 'required|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:1024',
            'plan_affaire' => 'required|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Stockage des fichiers
            $cniPath = $request->file('cni')->store('projets/cni', 'public');
            $pieceIdentitePath = $request->file('piece_identite')->store('projets/pieces_identite', 'public');
            $planAffairePath = $request->file('plan_affaire')->store('projets/plans_affaires', 'public');

            // Création du projet
            $projet = Projet::create([
                'nom' => $request->nom,
                'prenoms' => $request->prenoms,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'email' => $request->email,
                'type_projet' => $request->type_projet,
                'forme_juridique' => $request->forme_juridique,
                'num_cni' => $request->num_cni,
                'cni' => $cniPath,
                'piece_identite' => $pieceIdentitePath,
                'plan_affaire' => $planAffairePath,
                'statut' => 'en cours', // Statut par défaut
            ]);

            // Envoi de l'email de confirmation
            Mail::to($projet->email)->send(new ProjetEnregistre($projet));

            // Ajouter les URLs complètes pour les fichiers
            $projet->cni_url = Storage::url($projet->cni);
            $projet->piece_identite_url = Storage::url($projet->piece_identite);
            $projet->plan_affaire_url = Storage::url($projet->plan_affaire);

            return response()->json([
                'success' => true,
                'message' => 'Projet enregistré avec succès',
                'data' => $projet
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement du projet',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
