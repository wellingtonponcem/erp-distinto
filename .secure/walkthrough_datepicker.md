# Walkthrough - Implementação de Seletor de Datas (Flatpickr)

Implementamos um sistema de seleção de datas profissional e moderno utilizando a biblioteca **Flatpickr**, com suporte completo para o idioma português (Brasil).

## 🚀 Mudanças Realizadas

### 1. Integração Global
- Adicionamos a biblioteca Flatpickr (CSS, JS e Localização PT-BR) ao arquivo `includes/layout/head.php`.
- Configuramos um tema escuro para manter a consistência visual "Premium" da Distinto.

### 2. Módulo de Propostas
- **Data do Casamento**: Substituída por um seletor interativo com formato brasileiro (DD/MM/AAAA).
- **Data Limite para Desconto**: Agora utiliza o seletor de datas, evitando erros de digitação.
- **Data de Início & Validade**: Todos os campos de controle da proposta agora possuem seletores consistentes.

### 3. Módulo Financeiro
- **Filtros de Período**: Os filtros de "Início" e "Fim" nos lançamentos agora são mais fáceis de usar.
- **Vencimento & Término**: O cadastro de lançamentos foi otimizado para uma entrada de dados mais rápida e precisa.

## 🛠 Detalhes Técnicos
- **Localização**: Todos os seletores exibem dias e meses em português.
- **Compatibilidade**: Os dados continuam sendo enviados no formato padrão do banco de dados (YYYY-MM-DD) através do recurso `altInput` do Flatpickr, garantindo que nada quebre no backend.
- **Integração Alpine.js**: Utilizamos a diretiva `x-init` para garantir que o seletor seja inicializado corretamente, mesmo em componentes carregados dinamicamente.

---
**Commit Recomendado:** `feat: seletor de datas localizado (flatpickr)`
