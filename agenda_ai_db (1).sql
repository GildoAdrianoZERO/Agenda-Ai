-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/01/2026 às 15:49
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `agenda_ai_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `estabelecimento_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `data_hora_inicio` datetime NOT NULL,
  `data_hora_fim` datetime NOT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `cliente_telefone` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('agendado','confirmado','concluido','cancelado_cliente','cancelado_loja') DEFAULT 'agendado',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `profissional_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id`, `estabelecimento_id`, `servico_id`, `cliente_id`, `funcionario_id`, `data_hora_inicio`, `data_hora_fim`, `cliente_nome`, `cliente_telefone`, `observacoes`, `status`, `criado_em`, `profissional_id`) VALUES
(1, 1, 4, NULL, NULL, '2026-01-20 10:00:00', '2026-01-20 10:15:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-15 16:18:37', NULL),
(2, 1, 4, NULL, NULL, '2026-01-16 10:00:00', '2026-01-16 09:15:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-15 16:31:11', NULL),
(3, 1, 2, NULL, NULL, '2026-01-16 11:00:00', '2026-01-16 09:30:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-15 17:02:21', 3),
(4, 1, 4, NULL, NULL, '2026-01-15 09:00:00', '2026-01-15 09:15:00', 'Gildo', '81928293', NULL, 'cancelado_loja', '2026-01-15 17:35:23', NULL),
(5, 1, 4, NULL, NULL, '2026-01-16 18:00:00', '2026-01-15 11:15:00', 'adriano', '81928293', NULL, 'confirmado', '2026-01-15 17:35:41', 3),
(6, 1, 4, NULL, NULL, '2026-01-16 09:00:00', '2026-01-16 09:15:00', 'Gildo', '81928293', NULL, 'cancelado_loja', '2026-01-15 18:09:16', NULL),
(7, 1, 4, NULL, NULL, '2026-01-17 09:00:00', '2026-01-17 09:15:00', 'Gildo', '81928293', NULL, 'cancelado_loja', '2026-01-16 13:22:40', NULL),
(8, 1, 4, NULL, NULL, '2026-01-19 10:00:00', '2026-01-19 10:15:00', 'adriano', '81928293', NULL, 'confirmado', '2026-01-16 13:22:51', NULL),
(9, 1, 4, NULL, NULL, '2026-01-19 09:00:00', '0000-00-00 00:00:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-16 13:46:17', 3),
(10, 1, 4, NULL, NULL, '2026-01-22 11:00:00', '2026-01-17 10:15:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-16 13:46:35', NULL),
(11, 1, 4, NULL, NULL, '2026-01-17 11:00:00', '2026-01-17 11:15:00', 'adriano', '81928293', NULL, 'confirmado', '2026-01-16 13:47:56', 3),
(12, 1, 2, NULL, NULL, '2026-01-17 11:00:00', '2026-01-17 11:30:00', 'adriano', '81928293', NULL, 'confirmado', '2026-01-16 13:50:04', NULL),
(13, 1, 1, NULL, NULL, '2026-01-17 12:00:00', '2026-01-17 12:45:00', 'Gildo', '81928293', NULL, 'confirmado', '2026-01-16 13:50:16', 3),
(14, 1, 6, NULL, NULL, '2026-01-17 12:00:00', '2026-01-17 12:10:00', 'adriano', '81928293', NULL, 'confirmado', '2026-01-16 13:50:33', 3),
(15, 1, 4, NULL, NULL, '2026-01-19 10:00:00', '2026-01-19 10:15:00', 'adriano', '8199123919', NULL, 'confirmado', '2026-01-17 14:41:46', 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `estabelecimentos`
--

CREATE TABLE `estabelecimentos` (
  `id` int(11) NOT NULL,
  `nome_fantasia` varchar(100) NOT NULL,
  `descricao_curta` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto_capa` varchar(500) DEFAULT NULL,
  `avaliacao` decimal(2,1) DEFAULT 5.0,
  `qtd_avaliacoes` int(11) DEFAULT 0,
  `faixa_preco` enum('$','$$','$$$') DEFAULT '$$',
  `tags` varchar(255) DEFAULT NULL,
  `status_conta` enum('ativo','suspenso','inadimplente') DEFAULT 'ativo',
  `data_vencimento` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `categoria` enum('barbearia','salao','estetica','tatuagem','spa') DEFAULT 'barbearia',
  `horario_abertura` time DEFAULT '09:00:00',
  `horario_fechamento` time DEFAULT '19:00:00',
  `dias_funcionamento` varchar(255) DEFAULT '["1","2","3","4","5","6"]',
  `horario_almoco_inicio` time DEFAULT NULL,
  `horario_almoco_fim` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `estabelecimentos`
--

INSERT INTO `estabelecimentos` (`id`, `nome_fantasia`, `descricao_curta`, `endereco`, `telefone`, `foto_capa`, `avaliacao`, `qtd_avaliacoes`, `faixa_preco`, `tags`, `status_conta`, `data_vencimento`, `criado_em`, `categoria`, `horario_abertura`, `horario_fechamento`, `dias_funcionamento`, `horario_almoco_inicio`, `horario_almoco_fim`) VALUES
(1, 'Barbearia Teste Adriano', 'Ambiente seguro e moderno para todos', 'Rua Segura, 109', '23123', 'assets/uploads/capa_1_696b97038461d.jpg', 5.0, 0, '$$', NULL, 'ativo', '2030-12-31', '2026-01-15 14:34:25', 'barbearia', '09:00:00', '18:00:00', '[\"1\",\"3\",\"5\"]', NULL, NULL),
(22, 'Recife Barber Club', 'A barbearia mais clássica de Boa Viagem. Cerveja gelada e toalha quente.', 'Av. Conselheiro Aguiar, 1234 - Boa Viagem, Recife - PE', NULL, 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=800&q=80', 4.9, 450, '$$', 'Barba, Cabelo, Bigode, Cerveja', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'barbearia', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(23, 'Dom Hélio Barbearia', 'Estilo moderno no coração da Zona Norte. Especialista em degradê.', 'Estrada do Encanamento, 800 - Casa Forte, Recife - PE', NULL, 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=800&q=80', 4.7, 120, '$$', 'Degradê, Pigmentação, Corte Infantil', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'barbearia', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(24, 'Old School Cuts', 'Cortes tradicionais e ambiente retrô no Recife Antigo.', 'Rua do Bom Jesus, 150 - Recife Antigo, Recife - PE', NULL, 'https://images.unsplash.com/photo-1503951914205-22f2ca7c4c77?w=800&q=80', 4.8, 310, '$$$', 'Navalha, Retrô, Rock', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'barbearia', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(25, 'Studio Mulher Elegante', 'Especialistas em loiros e mechas. Saia daqui transformada.', 'Av. Gov. Agamenon Magalhães, 2000 - Espinheiro, Recife - PE', NULL, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&q=80', 5.0, 89, '$$$', 'Loiro, Mechas, Hidratação, Unhas', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'salao', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(26, 'Espaço Beleza Natural', 'Focado em cabelos cacheados e transição capilar.', 'Rua da Aurora, 500 - Boa Vista, Recife - PE', NULL, 'https://images.unsplash.com/photo-1634449571010-02389ed0f9b0?w=800&q=80', 4.6, 205, '$$', 'Cachos, Transição, Fitagem', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'salao', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(27, 'Ink Master Recife', 'Tatuagens realistas e Old School. O melhor traço da cidade.', 'Rua das Graças, 100 - Graças, Recife - PE', NULL, 'https://images.unsplash.com/photo-1611501275019-9b5cda994e8d?w=800&q=80', 4.9, 500, '$$$', 'Realismo, Old School, Piercing', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'tatuagem', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(28, 'Black Rose Tattoo', 'Estúdio privado focado em fineline e pontilhismo.', 'Rua Real da Torre, 400 - Madalena, Recife - PE', NULL, 'https://images.unsplash.com/photo-1598371839696-5c5bb00bdc28?w=800&q=80', 4.8, 150, '$$', 'Fineline, Delicada, Floral', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'tatuagem', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(29, 'Clínica Corpo & Face', 'Harmonização facial e drenagem linfática.', 'Av. Boa Viagem, 5000 - Boa Viagem, Recife - PE', NULL, 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80', 4.7, 98, '$$$', 'Botox, Drenagem, Limpeza de Pele', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'estetica', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(30, 'Oásis Urbano Spa', 'Relaxe no meio da cidade. Massagens terapêuticas e pedras quentes.', 'Rua do Futuro, 200 - Jaqueira, Recife - PE', NULL, 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800&q=80', 5.0, 42, '$$$', 'Massagem, Relax, Pedras Quentes', 'ativo', '2030-12-31', '2026-01-15 15:53:02', 'spa', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(31, 'Barbearia do Calote', 'Esta barbearia não pagou e deve sumir da lista.', 'Rua Errada, 00 - Pina, Recife - PE', NULL, 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a', 2.0, 5, '$', 'Ruim', 'inadimplente', '2023-01-01', '2026-01-15 15:53:02', 'barbearia', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL),
(32, 'Viking barber', NULL, NULL, NULL, NULL, 5.0, 0, '$$', NULL, 'ativo', '0000-00-00', '2026-01-17 14:46:58', 'barbearia', '09:00:00', '19:00:00', '[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissionais`
--

CREATE TABLE `profissionais` (
  `id` int(11) NOT NULL,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `foto` varchar(255) DEFAULT NULL,
  `funcao` varchar(100) DEFAULT 'Profissional',
  `inicio_intervalo` time DEFAULT NULL,
  `fim_intervalo` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `profissionais`
--

INSERT INTO `profissionais` (`id`, `estabelecimento_id`, `nome`, `ativo`, `foto`, `funcao`, `inicio_intervalo`, `fim_intervalo`) VALUES
(3, 1, 'Gildo Adriano Norberto Da Silva', 1, NULL, 'Profissional', NULL, NULL),
(5, 1, 'Jose da silva', 1, NULL, 'Atendente', NULL, NULL),
(6, 1, 'FULANO', 1, 'assets/uploads/696ba00b368a9.jfif', 'Profissional', '12:00:00', '13:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `duracao_minutos` int(11) DEFAULT 30,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `estabelecimento_id`, `nome`, `descricao`, `preco`, `duracao_minutos`, `ativo`) VALUES
(1, 1, 'Corte Cabelo', 'Corte moderno com tesoura e máquina.', 50.00, 45, 1),
(2, 1, 'Barba Completa', 'Barba terapia com toalha quente.', 40.00, 30, 1),
(3, 1, 'Combo (Corte + Barba)', 'Pacote completo para renovar o visual.', 80.00, 60, 1),
(4, 1, 'Pezinho / Acabamento', 'Apenas contornos.', 20.00, 15, 1),
(6, 1, 'Corte navalhado', NULL, 50.50, 10, 1),
(7, 1, 'APLICAÇÃO DE LUZ', NULL, 100.00, 60, 1),
(8, 32, 'Corte Masculino', NULL, 35.00, 40, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `estabelecimento_id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','funcionario') DEFAULT 'admin',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `estabelecimento_id`, `nome`, `email`, `senha`, `nivel`, `criado_em`) VALUES
(1, 1, 'Gildo Admin', 'admin@teste.com', '$2y$10$HpV5rREOdj559wQVbwFev.6F5l4rTd73UhHuV9lnvI2mH8kvx8XDK', 'admin', '2026-01-15 16:24:36'),
(2, 32, 'Gildo Silva', 'teste@gmail.com', '$2y$10$ijYzvhcmKm4PgXm1YS09AO7QPUoDNfxtUv89Q5uADPz0WmYtbVpdS', 'admin', '2026-01-17 14:46:59');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estabelecimento_id` (`estabelecimento_id`),
  ADD KEY `servico_id` (`servico_id`),
  ADD KEY `fk_agenda_profissional` (`profissional_id`);

--
-- Índices de tabela `estabelecimentos`
--
ALTER TABLE `estabelecimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `profissionais`
--
ALTER TABLE `profissionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estabelecimento_id` (`estabelecimento_id`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estabelecimento_id` (`estabelecimento_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `estabelecimento_id` (`estabelecimento_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `estabelecimentos`
--
ALTER TABLE `estabelecimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `profissionais`
--
ALTER TABLE `profissionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_agenda_profissional` FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `profissionais`
--
ALTER TABLE `profissionais`
  ADD CONSTRAINT `profissionais_ibfk_1` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`);

--
-- Restrições para tabelas `servicos`
--
ALTER TABLE `servicos`
  ADD CONSTRAINT `servicos_ibfk_1` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
