#!/bin/bash
echo "🚀 Compilando Tailwind CSS para Produção..."
npx -y tailwindcss@3 -i ./assets/css/input.css -o ./assets/css/tailwind.css --minify
echo "✅ Tailwind CSS compilado com sucesso em assets/css/tailwind.css"
