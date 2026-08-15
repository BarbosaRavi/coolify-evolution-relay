<?php

return [
    'url'            => rtrim(env('EVOLUTION_URL', ''), '/'),
    'api_key'        => env('EVOLUTION_API_KEY'),
    'instance'       => env('EVOLUTION_INSTANCE', 'deploys'),
    'group_jid'      => env('WHATSAPP_GROUP_JID'),
    'webhook_secret' => env('DEPLOY_WEBHOOK_SECRET'),
];
