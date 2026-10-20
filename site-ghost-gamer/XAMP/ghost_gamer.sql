-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05/10/2026 às 18:31
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ghost_gamer`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `biblioteca`
--

CREATE TABLE `biblioteca` (
  `id_biblioteca` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_jogo` int(11) NOT NULL,
  `data_adicionado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `biblioteca`
--

INSERT INTO `biblioteca` (`id_biblioteca`, `id_cliente`, `id_jogo`, `data_adicionado`) VALUES
(1, 21, 7, '2026-09-05 23:10:13'),
(2, 21, 11, '2026-09-06 00:01:14'),
(4, 21, 15, '2026-09-06 01:20:26'),
(5, 21, 8, '2026-10-03 02:34:31'),
(7, 21, 18, '2026-10-03 19:00:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `cli_nome` varchar(110) DEFAULT NULL,
  `email` varchar(110) DEFAULT NULL,
  `nickname` varchar(110) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `administrador` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `token_lembrar` varchar(64) DEFAULT NULL,
  `token_lembrar_expira` datetime DEFAULT NULL,
  `token_recuperacao` varchar(6) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `tentativas_login` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `cli_nome`, `email`, `nickname`, `foto`, `senha`, `administrador`, `token_lembrar`, `token_lembrar_expira`, `token_recuperacao`, `token_expira`, `tentativas_login`, `bloqueado_ate`) VALUES
(14, 'Gustavo Almeida', 'gustavo.almeida@gmail.com', 'GhostGus', NULL, '', 'usuario', NULL, NULL, NULL, NULL, 0, NULL),
(15, 'Marina Oliveira', 'marina.oliveira@gmail.com', 'MariGame', NULL, '', 'usuario', NULL, NULL, NULL, NULL, 0, NULL),
(16, 'Camila Rodrigues', 'camila.rodrigues@gmail.com', 'KamiGG', NULL, '$2y$10$ExaW.Jiogt/tGzAU5jpZK.Oh/asT.d3p6BSr8m7tMFarZ1F22yD7S', 'usuario', NULL, NULL, NULL, NULL, 0, NULL),
(17, 'Lucas Ferreira', 'lucas.ferreira@gmail.com', 'LkzFPS', NULL, '$2y$10$LECIQz5mnKpH0Sz9HMYeg.JRFZxdSD6aKPXGL7DDtS7sX0TMaeZgC', 'usuario', NULL, NULL, NULL, NULL, 0, NULL),
(18, 'Rafael Santos', 'rafael.santos@gmail.com', 'RafaX', NULL, '$2y$10$5eMUe2ickq8E9j6gDNlakuIHurKzuVqUvfSiP7Q7yOa2DakH6zmPG', 'usuario', NULL, NULL, NULL, NULL, 0, NULL),
(21, 'Gustavo Moreira', 'gustavoxmoreira2008@gmail.com', 'Gus', NULL, '$2y$10$neGXY7/rNYxeB.1SVDYT4.B8u7wqmlfslQjkGd5IcUAMW6teHQWFm', 'admin', '9a3da65f9fc227328c87b98300a94885922eec1d3111f9df9c323e8758be3f78', '2026-11-04 17:24:07', NULL, NULL, 0, NULL),
(23, 'Lucas Magalhaes', 'lucas.magalhaes31102008@gmail.com', 'Lucas', NULL, '$2y$10$bAcdoLDjrbZxrPgvzyrehOpGUbTqAn0PvJcZ1QYewJis.1KWh309q', 'admin', NULL, NULL, NULL, NULL, 0, NULL),
(24, 'Lucas Santos', 'lucasbernardinellisan@gmail.com', 'Havyto', NULL, '$2y$10$Cx3oIAHR7c8apxkREQo9yOQJsSiED9lXc/J6tZY/m.O/1BlbIWhC2', 'admin', NULL, NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresa`
--

CREATE TABLE `empresa` (
  `id_empresa` int(11) NOT NULL,
  `razao_social` varchar(155) NOT NULL,
  `nome_fantasia` varchar(150) NOT NULL,
  `CNPJ` varchar(14) NOT NULL,
  `data_abertura` date DEFAULT NULL,
  `telefone` varchar(14) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `Rua` varchar(100) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `bairro` varchar(80) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` char(8) DEFAULT NULL,
  `pais` char(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `empresa`
--

INSERT INTO `empresa` (`id_empresa`, `razao_social`, `nome_fantasia`, `CNPJ`, `data_abertura`, `telefone`, `email`, `Rua`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `pais`) VALUES
(1, 'TechNova Games Ltda', 'Ghost Gamer Studios', '12.345.678/000', '2022-08-12', '(11) 98765-432', 'contato@ghostgamer.com', 'Rua das Gameplays', '245', 'Centro', 'SÃ£o Paulo', 'SP', '01001-00', 'Brasil'),
(2, 'PixelStorm Tecnologia Digital Ltda', 'PixelStorm Games', '98.765.432/000', '2012-05-03', '(21) 97654-321', 'suporte@pixelstorm.com', 'Avenida Digital World', '880', 'Vila TecnolÃ³gica', 'Rio de Janeiro', 'RJ', '20040-02', 'Brasil'),
(3, 'NeoArcade Entertainment S.A.', 'NeoArcade', '45.678.901/000', '2002-11-28', '(31) 99876-543', 'contato@neoarcade.com', 'Rua dos E-Sports', '1020', 'Savassi', 'Belo Horizonte', 'MG', '30140-11', 'Brasil'),
(4, 'VegetaTecnologia', 'VegeTec', '123906423232', '2014-05-09', '20993784723', 'VegetablesTec@gmail.com', 'Rua Abara Silva Campos', '453', 'SÃ£o Arnaldo', 'Montes Claros', 'MG', '23784745', 'Brasil'),
(5, 'Heartsetups', 'Heartups', '384612044', '1999-08-28', '196439203', 'heartup@gmail.com', 'Avenida Borges de Medeiros', '33', 'Vila SuÃ­Ã§a', 'Gramado', 'RS', '34526637', 'Brasil'),
(7, 'LightzZZ', 'LightzZZ', '434398093534', '2006-01-03', '1893874893', 'Lightzz@gmail.com', 'Rua New Armstrong', '999', 'Bayro', 'UberlÃ¢ndia', 'SP', '54343973', 'Brasil');

-- --------------------------------------------------------

--
-- Estrutura para tabela `gpu`
--

CREATE TABLE `gpu` (
  `id_gpu` int(11) NOT NULL,
  `modelo` varchar(40) DEFAULT NULL,
  `marca` varchar(20) DEFAULT NULL,
  `fabricante` varchar(20) DEFAULT NULL,
  `VRAM` int(10) UNSIGNED DEFAULT NULL,
  `tipo_memoria` varchar(20) DEFAULT NULL,
  `clock` int(10) UNSIGNED DEFAULT NULL,
  `boost` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogo`
--

CREATE TABLE `jogo` (
  `id_jogo` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `empresa_email` varchar(110) DEFAULT NULL,
  `genero` varchar(110) DEFAULT NULL,
  `nucleos` int(10) UNSIGNED DEFAULT NULL,
  `threads` int(10) UNSIGNED DEFAULT NULL,
  `frequencia` decimal(10,0) DEFAULT NULL,
  `ram_gb` int(10) UNSIGNED DEFAULT NULL,
  `vram_gb` int(10) UNSIGNED DEFAULT NULL,
  `armazenamento` int(10) UNSIGNED DEFAULT NULL,
  `arquivo_jogo` varchar(255) DEFAULT NULL,
  `arquivo_nome_original` varchar(255) DEFAULT NULL,
  `arquivo_tamanho_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `downloads` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `jogo`
--

INSERT INTO `jogo` (`id_jogo`, `titulo`, `foto`, `empresa_email`, `genero`, `nucleos`, `threads`, `frequencia`, `ram_gb`, `vram_gb`, `armazenamento`, `arquivo_jogo`, `arquivo_nome_original`, `arquivo_tamanho_bytes`, `downloads`) VALUES
(4, 'GOGOGOZIIIIIG', NULL, 'LucasberingelaInc@gmail.com', 'Simulacao', 3, 3, 4, 4, 5, 6, NULL, NULL, NULL, 0),
(7, 'BombPotato', NULL, 'VegetablesTec@gmail.com', 'Indie', 3, 4, 3, 6, 1, 20, NULL, NULL, NULL, 0),
(8, 'Godzilla Disaster', NULL, 'VegetablesTec@gmail.com', 'Luta', 5, 4, 4, 8, 4, 50, NULL, NULL, NULL, 0),
(9, 'Junker Hospital', NULL, 'heartup@gmail.com', 'Simulacao', 3, 2, 2, 6, 2, 30, NULL, NULL, NULL, 0),
(10, 'Door Runners', NULL, 'suporte@pixelstorm.com', 'Corrida', 4, 5, 5, 8, 6, 68, NULL, NULL, NULL, 0),
(11, 'Darkeness Spike', NULL, 'suporte@pixelstorm.com', 'Terror', 6, 5, 6, 8, 8, 80, NULL, NULL, NULL, 0),
(12, 'O Amor Ã© Cego', NULL, 'suporte@pixelstorm.com', 'Musical', 7, 7, 7, 7, 7, 7, NULL, NULL, NULL, 0),
(15, 'PixelDragon', 'ASSETS/IMG/PixelDragon_6a9cbfc11b8a2.jpg', '', 'Indie', 3, 4, 5, 6, 10, 20, NULL, NULL, NULL, 0),
(18, 'Pré-Historic Hotel', 'ASSETS/IMG/Pr___Historic_Hotel_6ac150aa144f6.jpg', 'VegetablesTec@gmail.com', 'Sobrevivência', 4, 5, 3, 4, 1, 1, 'ASSETS/DOWNLOADS/Pr___Historic_Hotel_6ac150aa156ea.zip', 'corridacacto.zip', 109761395, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `memoria_ram`
--

CREATE TABLE `memoria_ram` (
  `id_ram` int(11) NOT NULL,
  `MODELO` varchar(40) DEFAULT NULL,
  `MARCA` varchar(20) DEFAULT NULL,
  `CAPACIDADE` int(10) UNSIGNED DEFAULT NULL,
  `TIPO` varchar(20) DEFAULT NULL,
  `FREQUENCIA` int(10) UNSIGNED DEFAULT NULL,
  `MODULOS` int(10) UNSIGNED DEFAULT NULL,
  `LATENCIA` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `processador`
--

CREATE TABLE `processador` (
  `id_processador` int(11) NOT NULL,
  `modelo` varchar(40) DEFAULT NULL,
  `marca` varchar(20) DEFAULT NULL,
  `geracao` varchar(20) DEFAULT NULL,
  `nucleo` int(10) UNSIGNED DEFAULT NULL,
  `threads` int(10) UNSIGNED DEFAULT NULL,
  `clock_base` decimal(4,2) DEFAULT NULL,
  `boost` decimal(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `biblioteca`
--
ALTER TABLE `biblioteca`
  ADD PRIMARY KEY (`id_biblioteca`),
  ADD UNIQUE KEY `jogo_usuario` (`id_cliente`,`id_jogo`),
  ADD KEY `id_jogo` (`id_jogo`),
  ADD KEY `idx_biblioteca_data` (`data_adicionado`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_clientes_nickname` (`nickname`);

--
-- Índices de tabela `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id_empresa`);

--
-- Índices de tabela `gpu`
--
ALTER TABLE `gpu`
  ADD PRIMARY KEY (`id_gpu`);

--
-- Índices de tabela `jogo`
--
ALTER TABLE `jogo`
  ADD PRIMARY KEY (`id_jogo`),
  ADD KEY `idx_jogo_genero` (`genero`),
  ADD KEY `idx_jogo_empresa_email` (`empresa_email`);

--
-- Índices de tabela `memoria_ram`
--
ALTER TABLE `memoria_ram`
  ADD PRIMARY KEY (`id_ram`);

--
-- Índices de tabela `processador`
--
ALTER TABLE `processador`
  ADD PRIMARY KEY (`id_processador`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `biblioteca`
--
ALTER TABLE `biblioteca`
  MODIFY `id_biblioteca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `gpu`
--
ALTER TABLE `gpu`
  MODIFY `id_gpu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jogo`
--
ALTER TABLE `jogo`
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `memoria_ram`
--
ALTER TABLE `memoria_ram`
  MODIFY `id_ram` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `processador`
--
ALTER TABLE `processador`
  MODIFY `id_processador` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `biblioteca`
--
ALTER TABLE `biblioteca`
  ADD CONSTRAINT `fk_biblioteca_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_biblioteca_jogo` FOREIGN KEY (`id_jogo`) REFERENCES `jogo` (`id_jogo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
