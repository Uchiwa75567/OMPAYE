#!/bin/bash

# Script d'arrêt OMPAYE
echo "🛑 Arrêt des services OMPAYE..."

# Arrêter les services Docker
docker-compose -f docker-compose.simple.yml down
docker-compose -f docker-compose.yml down 2>/dev/null || true

echo "✅ Services OMPAYE arrêtés"