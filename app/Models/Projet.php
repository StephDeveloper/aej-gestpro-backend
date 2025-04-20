<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
        'email',
        'type_projet',
        'forme_juridique',
        'num_cni',
        'cni',
        'piece_identite',
        'plan_affaire',
        'statut',
    ];
}
