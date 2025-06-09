<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// General notifications channel (for admins)
Broadcast::channel('notifications', function ($user) {
    return $user->hasRole('admin');
});

// User-specific notifications channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Project-specific channels
Broadcast::channel('project.{id}', function ($user, $id) {
    // User can listen if they are assigned to the project or are admin/manager
    return $user->hasRole(['admin', 'manager']) || 
           $user->projects()->where('id', $id)->exists();
});

// Payment-specific channels
Broadcast::channel('payment.{id}', function ($user, $id) {
    // User can listen if they are assigned to the payment or are admin/manager
    return $user->hasRole(['admin', 'manager']) || 
           $user->payments()->where('id', $id)->exists();
});
