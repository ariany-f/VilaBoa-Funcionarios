# Portal do Colaborador - Vila Boa Construções

Aplicação web moderna, responsiva com visual e usabilidade de **Aplicativo Mobile Nativo** (iOS/Android feel) e **Portal Desktop Corporativo**, desenvolvida para os colaboradores da **Vila Boa Construções**.

---

## 🎯 Objetivo Primário da Aplicação

Garantir que **100% dos colaboradores ativos mantenham seu número de telefone celular / WhatsApp confirmado e atualizado** a cada acesso, antes de liberarem a visualização de seus demonstrativos de pagamento (holerites) e informes da empresa.

---

## 📲 Fluxo de Acesso do Colaborador

1. **Login Inicial**:
   - Digitação do **CPF** (com máscara automática `000.000.000-00`).
   - Digitação da **Data de Nascimento** (com máscara `DD/MM/AAAA`).
2. **Confirmação de Segurança (Dado Pessoal Aleatório)**:
   - Sorteio dinâmico pelo backend de uma pergunta de validação cadastral (Ano de admissão na Vila Boa Construções, primeiros dígitos do RG, dia do aniversário ou nome da mãe) no padrão dos aplicativos bancários e Gov.br.
3. **Validação & Atualização Obrigatória do Telefone (Objetivo Principal)**:
   - Exibe o telefone atualmente cadastrado.
   - O colaborador confirma se o número ainda é o mesmo ou informa seu novo número com validação de DDD e 9 dígitos.
   - A confirmação é gravada com carimbo de data/hora no backend.
4. **Portal / Dashboard do Colaborador (Desktop & Mobile)**:
   - Salário Líquido mais recente com opção de ocultar/exibir saldo.
   - Histórico de holerites por competência/mês.
   - Detalhamento completo do holerite (Proventos, Descontos, Adicional de Obras/Periculosidade, Bases de Cálculo FGTS/INSS/IRRF, Dados Bancários).
   - Botão para Salvar / Imprimir PDF do demonstrativo no formato oficial A4.
   - Aba com dados cadastrais da ficha funcional e selo de telefone verificado.

---

## 🦺 Módulo de EPIs com Assinatura Eletrônica Válida (NR-06)

Em conformidade com a **NR-06 do Ministério do Trabalho e Emprego (Portaria MTP nº 672/2021)** e a **Lei Federal nº 14.063/2020** (Assinatura Eletrônica Avançada):

- **Ficha Eletrônica de EPI**: Registro dos equipamentos fornecidos, fabricante, data, motivo e número do **CA (Certificado de Aprovação)**.
- **Assinatura Biométrica / Touch Interativa**: O colaborador assina diretamente na tela do smartphone com o dedo ou no computador com o mouse.
- **Trilha de Auditoria e Validade Jurídica**:
  - Geração automática de **Chave Criptográfica SHA-256** inviolável vinculando itens, data, hora, IP, dispositivo e CPF do colaborador.
  - Termo de Responsabilidade e Guarda conforme a legislação trabalhista vigente.
  - Emissão da **Ficha Oficial em formato PDF / Impressão A4** pronta para apresentação à fiscalização do trabalho e auditorias de canteiro de obras.

---

## 🎨 Identidade Visual Oficial (vilaboa.com.br)

- **Cor Primária de Ação / Ícone da Marca**: `#BA2E45` / `#B82E45` (Vermelho Rubi Oficial dos botões e do logotipo).
- **Cor Institucional Escura**: `#1F133A` (Dark Navy / Ameixa profundo das seções nobres do site).
- **Tipografia e Texto**: `#2E2C31` (Cinza chumbo das letras da marca).
- **Fundos Neutros**: `#FFFFFF` e `#F8FAFC`.

A tela de login possui botões de 1 clique para auto-preenchimento rápido com qualquer um dos perfis abaixo:

| Nome | CPF | Data Nasc. | Cargo / Departamento | Telefone Atual |
| :--- | :--- | :--- | :--- | :--- |
| **Carlos Eduardo Ribeiro** | `123.456.789-00` | `15/04/1988` | Operador de Máquinas Pesadas • Obras | (62) 99123-4567 |
| **Juliana Silveira Ramos** | `987.654.321-11` | `28/09/1994` | Analista de RH - Obras • RH & DP | (62) 98455-1122 |
| **Marcos Vinicius Santos** | `111.222.333-44` | `10/01/2000` | Encarregado de Obras • Canteiro | (62) 99988-7766 |

---

## 🚀 Como Executar o Projeto

### Opção 1: Execução com 1 Clique (Recomendada)
Dê um duplo clique no arquivo:
```bat
iniciar.bat
```
Ele iniciará o servidor PHP embutido e abrirá o navegador em `http://localhost:8000`.

### Opção 2: Modo Desenvolvimento (Hot-Reload)
Dê um duplo clique no arquivo:
```bat
iniciar-dev.bat
```
Ele iniciará a API PHP na porta `8000` e o servidor Vite com Hot Module Replacement na porta `5173`.

---

## 🔌 Estrutura de Conexão com o WebService de RH (com Token)

O serviço de holerite está centralizado em `backend/services/HoleriteService.php`.

Para alternar do **Modo Mock** para o **WebService Real de Produção**:
1. Defina as variáveis de ambiente no servidor (ou altere no construtor da classe):
   - `VILABOA_RH_API_URL`: URL base da API do ERP/RH.
   - `VILABOA_RH_API_TOKEN`: Token de autenticação Bearer fornecido pelo RH.
2. A classe já possui a rotina cURL implementada (`consultarWebServiceRemoto()`) com headers seguros (`Authorization: Bearer <TOKEN>`, `Content-Type: application/json` e `X-Client-App`).

---

## 📂 Estrutura de Pastas

```
Portal Funcionario VB/
├── backend/
│   ├── data/
│   │   └── colaboradores.json       # Mock de colaboradores e persistência do telefone
│   ├── services/
│   │   ├── ColaboradorService.php   # Autenticação, perguntas de segurança e atualização de fone
│   │   └── HoleriteService.php      # Camada de integração com WebService (Token Bearer / Mock)
│   └── public/
│       ├── index.php                # API RESTful e roteamento
│       ├── index.html               # SPA Frontend compilado
│       ├── .htaccess                # Configuração para servidores Apache / cPanel
│       └── assets/                  # Bundles CSS e JS do aplicativo
├── frontend/
│   ├── src/
│   │   ├── App.vue                  # Aplicação estilo App Mobile
│   │   ├── components/
│   │   │   └── HoleriteModal.vue    # Demonstrativo de pagamento detalhado com impressão
│   │   ├── utils/
│   │   │   └── masks.js             # Máscaras de CPF, Data, Telefone e Moeda
│   │   ├── style.css                # Tailwind CSS e estilos responsivos
│   │   └── main.js                  # Inicialização Vue 3
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── package.json
├── iniciar.bat                      # Início rápido (Standalone PHP)
├── iniciar-dev.bat                  # Início para desenvolvimento (Vite + PHP)
└── README.md
```
