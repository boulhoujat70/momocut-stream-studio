<?php
// Mets ta clé Mistral ici ou définis la variable d'environnement MOMOCUT_MISTRAL_API_KEY.
define('MISTRAL_API_KEY', getenv('MOMOCUT_MISTRAL_API_KEY') ?: '');
define('MISTRAL_API_URL', 'https://api.mistral.ai/v1/chat/completions');
define('MISTRAL_MODEL', getenv('MOMOCUT_MISTRAL_MODEL') ?: 'mistral-small-latest');
?>
