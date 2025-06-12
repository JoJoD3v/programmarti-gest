<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Work extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'assigned_user_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Work type options for forms
     */
    public static function getWorkTypes(): array
    {
        return [
            'Bug' => 'Bug',
            'Miglioramenti' => 'Miglioramenti',
            'Da fare' => 'Da fare',
        ];
    }

    /**
     * Status options
     */
    public static function getStatuses(): array
    {
        return [
            'In Sospeso' => 'In Sospeso',
            'Completato' => 'Completato',
        ];
    }

    /**
     * Relationships
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
