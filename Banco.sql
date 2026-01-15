-- --------------------------------------------------------
-- Estrutura do Banco de Dados: AgendaAí
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-03:00";

--
-- 1. Tabela: estabelecimentos
--
CREATE TABLE `estabelecimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_fantasia` varchar(150) NOT NULL,
  `subdominio` varchar(50) DEFAULT NULL,
  `foto_capa` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `descricao_curta` text DEFAULT NULL,
  `status_conta` enum('ativo','inativo','pendente') DEFAULT 'ativo',
  `horario_abertura` time DEFAULT '09:00:00',
  `horario_fechamento` time DEFAULT '19:00:00',
  `horario_almoco_inicio` time DEFAULT NULL,
  `horario_almoco_fim` time DEFAULT NULL,
  `dias_funcionamento` varchar(255) DEFAULT '["1","2","3","4","5","6"]',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 2. Tabela: servicos
--
CREATE TABLE `servicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `duracao_minutos` int(11) NOT NULL DEFAULT 30,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_servico_estabelecimento` (`estabelecimento_id`),
  CONSTRAINT `fk_servico_estabelecimento` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 3. Tabela: profissionais
--
CREATE TABLE `profissionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `funcao` varchar(100) DEFAULT 'Profissional',
  `foto` varchar(255) DEFAULT NULL,
  `inicio_intervalo` time DEFAULT NULL,
  `fim_intervalo` time DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_prof_estabelecimento` (`estabelecimento_id`),
  CONSTRAINT `fk_prof_estabelecimento` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 4. Tabela: agendamentos
--
CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estabelecimento_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(100) NOT NULL,
  `cliente_telefone` varchar(20) NOT NULL,
  `data_hora_inicio` datetime NOT NULL,
  `data_hora_fim` datetime DEFAULT NULL,
  `status` enum('agendado','confirmado','concluido','cancelado_cliente','cancelado_loja') DEFAULT 'agendado',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_agenda_estabelecimento` (`estabelecimento_id`),
  KEY `fk_agenda_servico` (`servico_id`),
  KEY `fk_agenda_profissional` (`profissional_id`),
  CONSTRAINT `fk_agenda_estabelecimento` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agenda_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agenda_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 5. Tabela: usuarios (Login do Painel)
--
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','funcionario') DEFAULT 'admin',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_estabelecimento` (`estabelecimento_id`),
  CONSTRAINT `fk_usuario_estabelecimento` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- DADOS INICIAIS (Para você não começar do zero)
--

-- 1. Cria a Barbearia
INSERT INTO `estabelecimentos` (`id`, `nome_fantasia`, `endereco`, `telefone`, `descricao_curta`, `horario_abertura`, `horario_fechamento`) VALUES
(1, 'Recife Barber Club', 'Av. Boa Viagem, 100 - Recife/PE', '81999999999', 'A melhor barbearia da cidade. Cerveja gelada e corte na régua.', '09:00:00', '19:00:00');

-- 2. Cria Serviços Básicos
INSERT INTO `servicos` (`estabelecimento_id`, `nome`, `preco`, `duracao_minutos`) VALUES
(1, 'Corte de Cabelo', 35.00, 40),
(1, 'Barba Completa', 25.00, 30),
(1, 'Combo (Corte + Barba)', 50.00, 60),
(1, 'Pezinho / Acabamento', 15.00, 15);

-- 3. Cria Profissionais
INSERT INTO `profissionais` (`estabelecimento_id`, `nome`, `funcao`, `inicio_intervalo`, `fim_intervalo`) VALUES
(1, 'Gildo Adriano', 'Master Barber', '12:00:00', '13:00:00'),
(1, 'João da Silva', 'Barbeiro', '13:00:00', '14:00:00');

-- 4. Cria Usuário Admin
-- Login: admin@teste.com
-- Senha: 123456 (Hash gerado)
INSERT INTO `usuarios` (`estabelecimento_id`, `nome`, `email`, `senha`) VALUES
(1, 'Gildo Admin', 'admin@teste.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm');

COMMIT;