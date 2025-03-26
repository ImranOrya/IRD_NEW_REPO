<?php

namespace App\Traits\template;

use Carbon\Carbon;
use App\Contracts\Encryptable;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Sway\Utils\StringUtils;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });

        static::updated(function ($model) {
            $model->logAudit('updated');
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });
        // Listen to the 'deleting' event to capture the model's state before it's deleted
        static::deleting(function ($model) {
            $model->beforeDeleteAudit();
        });
    }

    // Store the original values before the model is deleted
    protected function beforeDeleteAudit()
    {
        // Store the original values for auditing purposes
        $this->originalValuesBeforeDelete = $this->getOriginal();
    }

    // Method to log the audit data
    protected function logAudit($event)
    {
        // Get the current user (if available)
        $user = Request::user();
        $userType = $user ? get_class($user) : null;
        $userId = $user ? $user->id : null;

        // Get the old and new values
        $oldValues = null; // Default for 'created' and 'deleted' events
        $newValues = null; // Default for 'created' and 'deleted' events

        // For 'updated' events, get the original and changed values
        if ($event === 'updated') {
            $oldValues = $this->getOriginal(); // Get the original values before update
            $newValues = $this->getDirty();    // Get the updated values
        }

        // For 'created' events, set the new values (new model attributes)
        if ($event === 'created') {
            $newValues = $this->attributesToArray(); // Get all the attributes of the newly created model
        }

        // For 'deleted' events, use the model's attributes before deletion (no new values)
        if ($event === 'deleted') {
            $oldValues = $this->originalValuesBeforeDelete ?? $this->getOriginal(); // Access the manually stored original values
            // No new values for delete, so $newValues remains null
        }

        // Gather additional metadata (URL, IP address, User agent)
        $url = Request::url();
        $ipAddress = Request::ip();
        $userAgent = Request::header('User-Agent');

        // Save audit data into the 'audits' table
        $auditable_type = get_class($this);
        Audit::create([
            'user_type' => $userType != null ? StringUtils::getModelName($userType) : null,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => $auditable_type != null ? StringUtils::getModelName($auditable_type) : null,
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues ? json_encode($oldValues) : null, // Ensure null if no old values
            'new_values' => $newValues ? json_encode($newValues) : null, // Ensure null if no new values
            'url' => $url,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'tags' => null, // Optionally, add tags here
        ]);
    }
}
