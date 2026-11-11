-- Portal do Colaborador Vila Boa — esquema PostgreSQL (banco próprio do portal de funcionários).
-- Idempotente: pode rodar várias vezes (backend/scripts/instalar_banco.php).
-- Padrão dos dados: colunas tipadas para o que o portal filtra/ordena e a coluna "dados" (jsonb)
-- com o registro completo, para não perder nenhum campo do que hoje vive em backend/data/*.json.

-- ------------------------------------------------------------------ login e permissões
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255),
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  recovery_code VARCHAR(255),
  recovery_code_expires TIMESTAMP,
  cellphone VARCHAR(20),
  cpf VARCHAR(11),
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS users_cpf_unique ON users (cpf);
CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (lower(email));
CREATE INDEX IF NOT EXISTS users_cellphone ON users (cellphone);

CREATE TABLE IF NOT EXISTS roles (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS users_roles (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
  role_id INT NOT NULL REFERENCES roles (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS menus (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  url VARCHAR(255) NOT NULL,
  parent_id INT,
  "position" INT NOT NULL DEFAULT 0,
  icon VARCHAR(255),
  allow_delete SMALLINT NOT NULL DEFAULT 1,
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS roles_menus (
  id SERIAL PRIMARY KEY,
  role_id INT NOT NULL REFERENCES roles (id) ON DELETE CASCADE,
  menu_id INT NOT NULL REFERENCES menus (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS permission_screens (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  controller VARCHAR(80),
  "position" INT NOT NULL DEFAULT 0,
  is_active SMALLINT NOT NULL DEFAULT 1,
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS permission_actions (
  id SERIAL PRIMARY KEY,
  screen_id INT NOT NULL REFERENCES permission_screens (id) ON DELETE CASCADE,
  slug VARCHAR(120) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  group_name VARCHAR(120),
  action_kind VARCHAR(20) NOT NULL DEFAULT 'execute',
  route_controller VARCHAR(80),
  route_action VARCHAR(120),
  http_methods VARCHAR(60),
  enforce_route SMALLINT NOT NULL DEFAULT 0,
  ui_selector VARCHAR(255),
  meta TEXT,
  "position" INT NOT NULL DEFAULT 0,
  is_active SMALLINT NOT NULL DEFAULT 1,
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now(),
  UNIQUE (screen_id, slug)
);
CREATE INDEX IF NOT EXISTS permission_actions_route ON permission_actions (route_controller, route_action);

CREATE TABLE IF NOT EXISTS roles_permission_actions (
  id SERIAL PRIMARY KEY,
  role_id INT NOT NULL REFERENCES roles (id) ON DELETE CASCADE,
  permission_action_id INT NOT NULL REFERENCES permission_actions (id) ON DELETE CASCADE,
  created TIMESTAMP DEFAULT now(),
  modified TIMESTAMP DEFAULT now(),
  UNIQUE (role_id, permission_action_id)
);

-- ------------------------------------------------------------------ empresas e colaboradores
CREATE TABLE IF NOT EXISTS empresas (
  nome VARCHAR(120) PRIMARY KEY,
  logo VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS colaboradores (
  id INT PRIMARY KEY,
  matricula VARCHAR(30) NOT NULL UNIQUE,
  cpf VARCHAR(11) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  cargo VARCHAR(255),
  departamento VARCHAR(255),
  empresa VARCHAR(120),
  data_nascimento DATE,
  data_admissao DATE,
  dados JSONB NOT NULL DEFAULT '{}'
);
CREATE INDEX IF NOT EXISTS colaboradores_cpf ON colaboradores (cpf);

CREATE TABLE IF NOT EXISTS chamados (
  id VARCHAR(40) PRIMARY KEY,
  colaborador_id INT,
  matricula VARCHAR(30),
  tipo VARCHAR(120),
  assunto VARCHAR(255),
  status VARCHAR(60),
  data_abertura TIMESTAMP,
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS ferias (
  matricula VARCHAR(30) PRIMARY KEY,
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS epis (
  id VARCHAR(40) PRIMARY KEY,
  colaborador_id INT,
  matricula VARCHAR(30),
  data_entrega DATE,
  status VARCHAR(40),
  dados JSONB NOT NULL DEFAULT '{}'
);
CREATE INDEX IF NOT EXISTS epis_matricula ON epis (matricula);

CREATE TABLE IF NOT EXISTS ponto (
  matricula VARCHAR(30) NOT NULL,
  competencia VARCHAR(7) NOT NULL,
  dados JSONB NOT NULL DEFAULT '{}',
  PRIMARY KEY (matricula, competencia)
);

CREATE TABLE IF NOT EXISTS holerites_ciencias (
  chave VARCHAR(120) PRIMARY KEY,
  matricula VARCHAR(30),
  holerite_id VARCHAR(60),
  competencia VARCHAR(7),
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS whatsapp_notificacoes (
  id VARCHAR(60) PRIMARY KEY,
  colaborador_id INT,
  dados JSONB NOT NULL DEFAULT '{}'
);

-- ------------------------------------------------------------------ comunicados
CREATE TABLE IF NOT EXISTS comunicados (
  id VARCHAR(60) PRIMARY KEY,
  publicado_em TIMESTAMP,
  autor VARCHAR(255),
  titulo VARCHAR(255) NOT NULL,
  categoria VARCHAR(60),
  prioridade VARCHAR(30),
  fixado SMALLINT NOT NULL DEFAULT 0,
  empresa VARCHAR(120),
  dados JSONB NOT NULL DEFAULT '{}'
);

-- ------------------------------------------------------------------ cadernos (diretrizes e treinamentos)
CREATE TABLE IF NOT EXISTS cadernos_categorias (
  nome VARCHAR(120) PRIMARY KEY,
  posicao INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cadernos_config (
  arquivo VARCHAR(255) PRIMARY KEY,
  categoria VARCHAR(120),
  obrigatorio SMALLINT NOT NULL DEFAULT 0,
  publico VARCHAR(30),
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS cadernos_leituras (
  chave VARCHAR(400) PRIMARY KEY,
  matricula VARCHAR(30) NOT NULL,
  arquivo VARCHAR(255) NOT NULL,
  hash_arquivo VARCHAR(64),
  dados JSONB NOT NULL DEFAULT '{}'
);
CREATE INDEX IF NOT EXISTS cadernos_leituras_matricula ON cadernos_leituras (matricula);

-- ------------------------------------------------------------------ Academia
CREATE TABLE IF NOT EXISTS academia_trilhas (
  id VARCHAR(40) PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  obrigatoria SMALLINT NOT NULL DEFAULT 0,
  publicada SMALLINT NOT NULL DEFAULT 1,
  empresa VARCHAR(120),
  carga_horaria VARCHAR(20),
  prazo DATE,
  pontos INT,
  funcoes JSONB NOT NULL DEFAULT '[]',
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS academia_itens (
  trilha_id VARCHAR(40) NOT NULL REFERENCES academia_trilhas (id) ON DELETE CASCADE,
  id VARCHAR(40) NOT NULL,
  posicao INT NOT NULL DEFAULT 0,
  tipo VARCHAR(20) NOT NULL,
  titulo VARCHAR(255),
  descricao TEXT,
  url TEXT,
  arquivo VARCHAR(255),
  duracao VARCHAR(20),
  pontos INT,
  PRIMARY KEY (trilha_id, id)
);

CREATE TABLE IF NOT EXISTS academia_progresso (
  chave VARCHAR(200) PRIMARY KEY,
  matricula VARCHAR(30) NOT NULL,
  trilha_id VARCHAR(40) NOT NULL,
  item_id VARCHAR(40) NOT NULL,
  dados JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE IF NOT EXISTS academia_certificados (
  chave VARCHAR(200) PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  matricula VARCHAR(30) NOT NULL,
  trilha_id VARCHAR(40) NOT NULL,
  dados JSONB NOT NULL DEFAULT '{}'
);
