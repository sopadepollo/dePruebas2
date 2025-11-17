<?php
$pageTitle = 'Nuestras Sucursales';
require 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucursales - Las Sevillanas</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <main class="container" style="padding-top: 20px;">
        <h1>Nuestras Sucursales</h1>
        <p>Encuentra tu tienda Las Sevillanas más cercana en San Luis Potosí.</p>
        
        <div id="mapa-sucursales" style="height: 450px; width: 100%; border: 1px solid #ccc; margin-top: 20px;"></div>
    </main>
    
    <?php require 'templates/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el mapa centrado en SLP
        // (Estas coordenadas [22.15, -100.98] son el centro de SLP)
        const map = L.map('mapa-sucursales').setView([22.15, -100.98], 13);
        
        // Añadir la capa de OpenStreetMap (gratuita)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Cargar sucursales desde nuestro API
        fetch('api/sucursales.php')
            .then(res => res.json())
            .then(data => {
                if (data.sucursales) {
                    data.sucursales.forEach(sucursal => {
                        // Solo añadir marcador si tenemos latitud y longitud
                        if (sucursal.latitud && sucursal.longitud) {
                            L.marker([sucursal.latitud, sucursal.longitud]).addTo(map)
                                .bindPopup(`
                                    <b>${sucursal.nom_sucursal}</b><br>
                                    ${sucursal.direccion_surc}<br>
                                    Tel: ${sucursal.telefono}
                                `);
                        } else {
                            console.warn(`La sucursal ${sucursal.nom_sucursal} no tiene coordenadas.`);
                        }
                    });
                }
            })
            .catch(err => console.error('Error al cargar sucursales:', err));
    });
    </script>
</body>
</html>