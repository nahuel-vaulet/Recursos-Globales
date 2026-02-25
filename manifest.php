<?php
header('Content-Type: application/manifest+json');
?>
{
"name": "ERP Recursos Globales",
"short_name": "ERP RG",
"description": "Gestión de ODTs y operaciones para ASSA",
"start_url": "/APP-Prueba/index.php",
"scope": "/APP-Prueba/",
"display": "standalone",
"orientation": "portrait",
"background_color": "#1a1a2e",
"theme_color": "#0d1b2a",
"icons": [
{
"src": "/APP-Prueba/icons/icon-192.png",
"sizes": "192x192",
"type": "image/png",
"purpose": "any maskable"
},
{
"src": "/APP-Prueba/icons/icon-512.png",
"sizes": "512x512",
"type": "image/png",
"purpose": "any maskable"
}
],
"shortcuts": [
{
"name": "Calendario ODTs",
"short_name": "Calendario",
"url": "/APP-Prueba/modules/odt/calendar.php",
"icons": [{"src": "/APP-Prueba/icons/icon-192.png", "sizes": "192x192"}]
},
{
"name": "Lista ODTs",
"short_name": "ODTs",
"url": "/APP-Prueba/modules/odt/index.php",
"icons": [{"src": "/APP-Prueba/icons/icon-192.png", "sizes": "192x192"}]
}
],
"categories": [
"business",
"productivity",
"utilities"
]
}