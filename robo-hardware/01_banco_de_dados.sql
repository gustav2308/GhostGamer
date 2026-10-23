-- ============================================================
--  SISTEMA DE COMPATIBILIDADE DE HARDWARE
--  Banco de dados completo - Tabelas de CPU, GPU, RAM, etc.
-- ============================================================

-- ----------------------------------------------------------------
-- EXTENSÕES (PostgreSQL)
-- ----------------------------------------------------------------
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ================================================================
-- 1. FABRICANTES
-- ================================================================
CREATE TABLE fabricantes (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(50)  NOT NULL,   -- Intel, AMD, NVIDIA, Corsair...
    tipo        VARCHAR(20)  NOT NULL,           -- cpu | gpu | ram | storage | mb
    site        VARCHAR(200),
    criado_em   TIMESTAMP DEFAULT NOW()
);

INSERT INTO fabricantes (nome, tipo, site) VALUES
  ('Intel',   'cpu',     'https://ark.intel.com'),
  ('AMD',     'cpu',     'https://www.amd.com'),
  ('ARM',     'cpu',     'https://www.arm.com'),
  ('NVIDIA',  'gpu',     'https://www.nvidia.com'),
  ('AMD',     'gpu',     'https://www.amd.com'),
  ('Corsair', 'ram',     'https://www.corsair.com'),
  ('Kingston','ram',     'https://www.kingston.com'),
  ('Samsung', 'storage', 'https://www.samsung.com'),
  ('Western Digital','storage','https://www.westerndigital.com');


-- ================================================================
-- 2. CPUs  (Processadores)
-- ================================================================
CREATE TABLE cpus (
    id                  SERIAL PRIMARY KEY,
    fabricante          VARCHAR(20)  NOT NULL,           -- Intel | AMD | ARM
    familia             VARCHAR(50)  NOT NULL,           -- Core i9 | Ryzen 9 | Snapdragon...
    modelo              VARCHAR(100) NOT NULL UNIQUE,    -- Core i9-14900K
    geracao             VARCHAR(30),                     -- 14ª Geração / Zen 4
    geracao_numero      SMALLINT,                        -- 14
    socket              VARCHAR(30),                     -- LGA1700 | AM5 | AM4
    nucleos_total       SMALLINT,                        -- total (P+E cores)
    nucleos_performance SMALLINT,                        -- P-cores (Intel híbrido)
    nucleos_eficiencia  SMALLINT,                        -- E-cores (Intel híbrido)
    threads             SMALLINT,
    clock_base_ghz      NUMERIC(4,2),
    clock_boost_ghz     NUMERIC(4,2),
    cache_l2_mb         NUMERIC(6,2),
    cache_l3_mb         NUMERIC(6,2),
    tdp_w               SMALLINT,                        -- Thermal Design Power
    litografia_nm       SMALLINT,                        -- 7 | 10 | 12 | 14...
    ram_suportada       VARCHAR(30),                     -- DDR4 | DDR5 | DDR4/DDR5
    ram_max_gb          SMALLINT,
    ram_canais          SMALLINT,                        -- 1 | 2 | 4
    ram_velocidade_mhz  INT,
    gpu_integrada       BOOLEAN DEFAULT FALSE,
    gpu_integrada_nome  VARCHAR(50),
    pcie_versao         VARCHAR(10),                     -- 3.0 | 4.0 | 5.0
    pcie_lanes          SMALLINT,
    ano_lancamento      SMALLINT,
    preco_lancamento_usd NUMERIC(8,2),
    benchmark_cinebench_r23_single  INT,
    benchmark_cinebench_r23_multi   INT,
    benchmark_passmark              INT,
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW(),
    atualizado_em       TIMESTAMP DEFAULT NOW()
);

-- Index para buscas comuns
CREATE INDEX idx_cpus_fabricante    ON cpus (fabricante);
CREATE INDEX idx_cpus_socket        ON cpus (socket);
CREATE INDEX idx_cpus_geracao_num   ON cpus (geracao_numero);
CREATE INDEX idx_cpus_ano           ON cpus (ano_lancamento);

-- Exemplos de CPUs Intel (14ª geração)
INSERT INTO cpus (fabricante, familia, modelo, geracao, geracao_numero, socket,
    nucleos_total, nucleos_performance, nucleos_eficiencia, threads,
    clock_base_ghz, clock_boost_ghz, cache_l3_mb, tdp_w, litografia_nm,
    ram_suportada, ram_max_gb, ram_canais, pcie_versao, pcie_lanes,
    ano_lancamento, benchmark_cinebench_r23_single, benchmark_cinebench_r23_multi, benchmark_passmark)
VALUES
  ('Intel','Core i9','Core i9-14900K','14ª Geração',14,'LGA1700',24,8,16,32,3.2,6.0,36,125,10,'DDR4/DDR5',192,2,'5.0',20,2023,2253,41000,62000),
  ('Intel','Core i7','Core i7-14700K','14ª Geração',14,'LGA1700',20,8,12,28,3.4,5.6,33,125,10,'DDR4/DDR5',192,2,'5.0',20,2023,2100,35000,52000),
  ('Intel','Core i5','Core i5-14600K','14ª Geração',14,'LGA1700',14,6,8,20,3.5,5.3,24,125,10,'DDR4/DDR5',192,2,'5.0',20,2023,1970,25000,38000),
  ('Intel','Core i3','Core i3-14100',  '14ª Geração',14,'LGA1700', 4,4, 0, 8,3.5,4.7,12, 60,10,'DDR4/DDR5',192,2,'5.0',20,2024,1800, 9500,16000);

-- Exemplos de CPUs Intel (13ª geração)
INSERT INTO cpus (fabricante, familia, modelo, geracao, geracao_numero, socket,
    nucleos_total, nucleos_performance, nucleos_eficiencia, threads,
    clock_base_ghz, clock_boost_ghz, cache_l3_mb, tdp_w, litografia_nm,
    ram_suportada, ram_max_gb, ram_canais, pcie_versao, pcie_lanes, ano_lancamento)
VALUES
  ('Intel','Core i9','Core i9-13900K','13ª Geração',13,'LGA1700',24,8,16,32,3.0,5.8,36,125,10,'DDR4/DDR5',192,2,'5.0',20,2022),
  ('Intel','Core i7','Core i7-13700K','13ª Geração',13,'LGA1700',16,8, 8,24,3.4,5.4,30,125,10,'DDR4/DDR5',192,2,'5.0',20,2022),
  ('Intel','Core i5','Core i5-13600K','13ª Geração',13,'LGA1700',14,6, 8,20,3.5,5.1,24,125,10,'DDR4/DDR5',192,2,'5.0',20,2022);

-- Exemplos AMD Zen 4
INSERT INTO cpus (fabricante, familia, modelo, geracao, geracao_numero, socket,
    nucleos_total, threads, clock_base_ghz, clock_boost_ghz, cache_l3_mb, tdp_w,
    litografia_nm, ram_suportada, ram_max_gb, ram_canais, pcie_versao, pcie_lanes, ano_lancamento)
VALUES
  ('AMD','Ryzen 9','Ryzen 9 7950X','Zen 4',4,'AM5',16,32,4.5,5.7,64,170,5,'DDR5',128,2,'5.0',24,2022),
  ('AMD','Ryzen 9','Ryzen 9 7900X','Zen 4',4,'AM5',12,24,4.7,5.6,64,170,5,'DDR5',128,2,'5.0',24,2022),
  ('AMD','Ryzen 7','Ryzen 7 7700X','Zen 4',4,'AM5', 8,16,4.5,5.4,32,105,5,'DDR5',128,2,'5.0',24,2022),
  ('AMD','Ryzen 5','Ryzen 5 7600X','Zen 4',4,'AM5', 6,12,4.7,5.3,32,105,5,'DDR5',128,2,'5.0',24,2022);

-- Exemplos AMD Zen 3
INSERT INTO cpus (fabricante, familia, modelo, geracao, geracao_numero, socket,
    nucleos_total, threads, clock_base_ghz, clock_boost_ghz, cache_l3_mb, tdp_w,
    litografia_nm, ram_suportada, ram_max_gb, ram_canais, pcie_versao, pcie_lanes, ano_lancamento)
VALUES
  ('AMD','Ryzen 9','Ryzen 9 5950X','Zen 3',3,'AM4',16,32,3.4,4.9,64,105,7,'DDR4',128,2,'4.0',24,2020),
  ('AMD','Ryzen 7','Ryzen 7 5800X','Zen 3',3,'AM4', 8,16,3.8,4.7,32,105,7,'DDR4',128,2,'4.0',24,2020),
  ('AMD','Ryzen 5','Ryzen 5 5600X','Zen 3',3,'AM4', 6,12,3.7,4.6,32, 65,7,'DDR4',128,2,'4.0',24,2020);


-- ================================================================
-- 3. GPUs  (Placas de vídeo)
-- ================================================================
CREATE TABLE gpus (
    id                  SERIAL PRIMARY KEY,
    fabricante          VARCHAR(20)  NOT NULL,           -- NVIDIA | AMD | Intel
    familia             VARCHAR(50)  NOT NULL,           -- GeForce RTX | Radeon RX
    modelo              VARCHAR(100) NOT NULL UNIQUE,    -- RTX 4090
    geracao             VARCHAR(30),                     -- Ada Lovelace | RDNA 3
    arquitetura         VARCHAR(50),
    vram_gb             SMALLINT,
    vram_tipo           VARCHAR(20),                     -- GDDR6 | GDDR6X | HBM2
    vram_barramento_bit SMALLINT,                        -- 128 | 192 | 256 | 384
    cuda_cores          INT,                             -- NVIDIA
    stream_processors   INT,                             -- AMD
    clock_base_mhz      INT,
    clock_boost_mhz     INT,
    tdp_w               SMALLINT,
    conector_pcie       VARCHAR(20),                     -- PCIe 4.0 x16 | 5.0 x16
    saidas_video        VARCHAR(100),                    -- 3x DP 1.4, 1x HDMI 2.1
    resolucao_maxima    VARCHAR(20),
    ray_tracing         BOOLEAN DEFAULT FALSE,
    dlss_versao         VARCHAR(10),                     -- 2 | 3 | 3.5
    fsr_versao          VARCHAR(10),                     -- 1 | 2 | 3
    ano_lancamento      SMALLINT,
    preco_lancamento_usd NUMERIC(8,2),
    benchmark_3dmark_ts INT,
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_gpus_fabricante ON gpus (fabricante);
CREATE INDEX idx_gpus_vram       ON gpus (vram_gb);

INSERT INTO gpus (fabricante, familia, modelo, geracao, arquitetura, vram_gb, vram_tipo,
    vram_barramento_bit, cuda_cores, clock_boost_mhz, tdp_w, ray_tracing, dlss_versao, ano_lancamento, preco_lancamento_usd)
VALUES
  ('NVIDIA','GeForce RTX','RTX 4090','Ada Lovelace','Ada Lovelace',24,'GDDR6X',384,16384,2520,450,TRUE,'3.5',2022,1599),
  ('NVIDIA','GeForce RTX','RTX 4080','Ada Lovelace','Ada Lovelace',16,'GDDR6X',256,9728,2505,320,TRUE,'3.5',2022,1199),
  ('NVIDIA','GeForce RTX','RTX 4070 Ti','Ada Lovelace','Ada Lovelace',12,'GDDR6X',192,7680,2610,285,TRUE,'3.5',2023, 799),
  ('NVIDIA','GeForce RTX','RTX 4070','Ada Lovelace','Ada Lovelace',12,'GDDR6X',192,5888,2475,200,TRUE,'3.5',2023, 599),
  ('NVIDIA','GeForce RTX','RTX 4060','Ada Lovelace','Ada Lovelace', 8,'GDDR6', 128,3072,2460,115,TRUE,'3.5',2023, 299),
  ('AMD','Radeon RX','RX 7900 XTX','RDNA 3','RDNA 3',24,'GDDR6',384,NULL,2500,355,TRUE,NULL,2022, 999),
  ('AMD','Radeon RX','RX 7900 XT','RDNA 3','RDNA 3', 20,'GDDR6',320,NULL,2394,315,TRUE,NULL,2022, 899),
  ('AMD','Radeon RX','RX 7800 XT','RDNA 3','RDNA 3', 16,'GDDR6',256,NULL,2430,263,TRUE,NULL,2023, 499);


-- ================================================================
-- 4. MEMÓRIA RAM
-- ================================================================
CREATE TABLE memorias_ram (
    id                  SERIAL PRIMARY KEY,
    fabricante          VARCHAR(50)  NOT NULL,
    modelo              VARCHAR(100) NOT NULL,
    tipo                VARCHAR(10)  NOT NULL,           -- DDR4 | DDR5 | LPDDR5
    capacidade_gb       SMALLINT     NOT NULL,
    velocidade_mhz      INT          NOT NULL,           -- 3200 | 4800 | 6000
    latencia_cl         SMALLINT,                        -- CAS Latency
    timings             VARCHAR(30),                     -- 16-18-18-38
    voltagem            NUMERIC(4,2),
    canais              SMALLINT DEFAULT 1,              -- 1 = single, 2 = dual
    ecc                 BOOLEAN DEFAULT FALSE,
    perfil_xmp          BOOLEAN DEFAULT FALSE,
    perfil_expo         BOOLEAN DEFAULT FALSE,
    fator_forma         VARCHAR(20) DEFAULT 'DIMM',      -- DIMM | SO-DIMM
    ano_lancamento      SMALLINT,
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_ram_tipo        ON memorias_ram (tipo);
CREATE INDEX idx_ram_capacidade  ON memorias_ram (capacidade_gb);
CREATE INDEX idx_ram_velocidade  ON memorias_ram (velocidade_mhz);

INSERT INTO memorias_ram (fabricante, modelo, tipo, capacidade_gb, velocidade_mhz, latencia_cl, timings, voltagem, perfil_xmp)
VALUES
  ('Corsair','Vengeance RGB Pro','DDR4',16,3600,18,'18-22-22-42',1.35,TRUE),
  ('Corsair','Vengeance RGB Pro','DDR4',32,3200,16,'16-18-18-36',1.35,TRUE),
  ('G.Skill','Trident Z5 RGB','DDR5',32,6000,36,'36-36-36-96',1.35,TRUE),
  ('G.Skill','Trident Z5 RGB','DDR5',64,6000,36,'36-36-36-96',1.35,TRUE),
  ('Kingston','Fury Beast','DDR4',16,3200,16,'16-18-18-36',1.35,TRUE),
  ('Kingston','Fury Beast','DDR5',32,5200,40,'40-40-40-80',1.25,TRUE);


-- ================================================================
-- 5. ARMAZENAMENTO (SSD / HDD)
-- ================================================================
CREATE TABLE armazenamentos (
    id                  SERIAL PRIMARY KEY,
    fabricante          VARCHAR(50)  NOT NULL,
    modelo              VARCHAR(100) NOT NULL,
    tipo                VARCHAR(10)  NOT NULL,           -- SSD | HDD | NVMe
    interface           VARCHAR(20)  NOT NULL,           -- SATA | PCIe 4.0 | PCIe 5.0
    fator_forma         VARCHAR(20),                     -- M.2 2280 | 2.5" | 3.5"
    capacidade_gb       INT          NOT NULL,
    leitura_seq_mbps    INT,
    escrita_seq_mbps    INT,
    iops_leitura        INT,
    iops_escrita        INT,
    tbw                 INT,                             -- Terabytes Written
    mtbf_horas          INT,                             -- Mean Time Between Failures
    nand_tipo           VARCHAR(20),                     -- TLC | QLC | MLC | SLC
    dram_cache          BOOLEAN DEFAULT FALSE,
    ano_lancamento      SMALLINT,
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_storage_tipo      ON armazenamentos (tipo);
CREATE INDEX idx_storage_interface ON armazenamentos (interface);

INSERT INTO armazenamentos (fabricante, modelo, tipo, interface, fator_forma, capacidade_gb,
    leitura_seq_mbps, escrita_seq_mbps, nand_tipo, dram_cache, ano_lancamento)
VALUES
  ('Samsung','980 Pro','NVMe','PCIe 4.0','M.2 2280',1000,7000,5000,'MLC',TRUE,2020),
  ('Samsung','980 Pro','NVMe','PCIe 4.0','M.2 2280',2000,7000,5100,'MLC',TRUE,2020),
  ('Samsung','990 Pro','NVMe','PCIe 4.0','M.2 2280',2000,7450,6900,'TLC',TRUE,2022),
  ('WD','Black SN850X','NVMe','PCIe 4.0','M.2 2280',2000,7300,6600,'TLC',TRUE,2022),
  ('Seagate','Barracuda','HDD','SATA','3.5"',2000,180,180,NULL,FALSE,2021),
  ('Seagate','Barracuda','HDD','SATA','3.5"',4000,190,190,NULL,FALSE,2021);


-- ================================================================
-- 6. PLACAS-MÃE
-- ================================================================
CREATE TABLE motherboards (
    id                  SERIAL PRIMARY KEY,
    fabricante          VARCHAR(50)  NOT NULL,
    modelo              VARCHAR(100) NOT NULL,
    chipset             VARCHAR(30),                     -- Z790 | B650 | X670E
    socket              VARCHAR(30)  NOT NULL,           -- LGA1700 | AM5 | AM4
    fator_forma         VARCHAR(20),                     -- ATX | mATX | ITX | E-ATX
    slots_ram           SMALLINT,
    tipo_ram            VARCHAR(20),                     -- DDR4 | DDR5
    ram_max_gb          SMALLINT,
    ram_velocidade_max  INT,
    slots_pcie_x16      SMALLINT DEFAULT 1,
    slots_m2            SMALLINT DEFAULT 1,
    portas_sata         SMALLINT,
    usb_traseiro        VARCHAR(100),
    wifi                BOOLEAN DEFAULT FALSE,
    bluetooth           BOOLEAN DEFAULT FALSE,
    ano_lancamento      SMALLINT,
    preco_usd           NUMERIC(8,2),
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_mb_socket  ON motherboards (socket);
CREATE INDEX idx_mb_chipset ON motherboards (chipset);

INSERT INTO motherboards (fabricante, modelo, chipset, socket, fator_forma,
    slots_ram, tipo_ram, ram_max_gb, slots_m2, portas_sata, wifi, ano_lancamento, preco_usd)
VALUES
  ('ASUS','ROG Strix Z790-E','Z790','LGA1700','ATX',4,'DDR5',192,5,6,TRUE,2022,499),
  ('MSI','MAG B660M Mortar','B660','LGA1700','mATX',4,'DDR4',128,2,6,FALSE,2021,179),
  ('Gigabyte','X670E Aorus Master','X670E','AM5','ATX',4,'DDR5',128,4,4,TRUE,2022,499),
  ('ASUS','TUF Gaming B650-Plus','B650','AM5','ATX',4,'DDR5',128,4,4,TRUE,2022,219);


-- ================================================================
-- 7. APLICATIVOS (para comparação de requisitos)
-- ================================================================
CREATE TABLE aplicativos (
    id                  SERIAL PRIMARY KEY,
    nome                VARCHAR(100) NOT NULL,
    versao              VARCHAR(20),
    categoria           VARCHAR(50),                     -- Game | CAD | Video | Office
    desenvolvedor       VARCHAR(100),
    -- Requisitos mínimos
    req_min_cpu_modelo  VARCHAR(100),
    req_min_cpu_nucleos SMALLINT,
    req_min_cpu_ghz     NUMERIC(4,2),
    req_min_ram_gb      SMALLINT,
    req_min_gpu_vram_gb SMALLINT,
    req_min_storage_gb  INT,
    req_min_so          VARCHAR(50),
    req_min_directx     VARCHAR(10),
    -- Requisitos recomendados
    req_rec_cpu_modelo  VARCHAR(100),
    req_rec_cpu_nucleos SMALLINT,
    req_rec_cpu_ghz     NUMERIC(4,2),
    req_rec_ram_gb      SMALLINT,
    req_rec_gpu_vram_gb SMALLINT,
    req_rec_storage_gb  INT,
    req_rec_so          VARCHAR(50),
    req_rec_directx     VARCHAR(10),
    ativo               BOOLEAN DEFAULT TRUE,
    criado_em           TIMESTAMP DEFAULT NOW()
);

INSERT INTO aplicativos (nome, versao, categoria, desenvolvedor,
    req_min_cpu_nucleos, req_min_cpu_ghz, req_min_ram_gb, req_min_gpu_vram_gb, req_min_storage_gb, req_min_so, req_min_directx,
    req_rec_cpu_nucleos, req_rec_cpu_ghz, req_rec_ram_gb, req_rec_gpu_vram_gb, req_rec_storage_gb, req_rec_so, req_rec_directx)
VALUES
  ('Cyberpunk 2077','2.0','Game','CD Projekt Red',
   8,1.8,12,8,70,'Windows 10 64-bit','12',
   8,3.2,16,12,70,'Windows 10 64-bit','12'),
  ('Adobe Premiere Pro','24.0','Video','Adobe',
   8,2.0,16,4,16,'Windows 10 64-bit','12',
   16,3.2,32,8,32,'Windows 11 64-bit','12'),
  ('AutoCAD 2024','2024','CAD','Autodesk',
   4,2.5,8,1,10,'Windows 10 64-bit','11',
   8,3.0,16,4,20,'Windows 11 64-bit','12');


-- ================================================================
-- 8. SCAN DE PCs DOS USUÁRIOS
-- ================================================================
CREATE TABLE scans_pc (
    id                  SERIAL PRIMARY KEY,
    usuario_id          INT,                             -- FK para tabela de usuários
    hash_maquina        VARCHAR(64),                     -- identificador anônimo
    so                  VARCHAR(50),
    so_versao           VARCHAR(30),
    -- CPU detectada
    cpu_modelo          VARCHAR(100),
    cpu_nucleos         SMALLINT,
    cpu_threads         SMALLINT,
    cpu_clock_ghz       NUMERIC(4,2),
    cpu_id              INT REFERENCES cpus(id),         -- match com a tabela
    -- GPU detectada
    gpu_modelo          VARCHAR(100),
    gpu_vram_gb         SMALLINT,
    gpu_id              INT REFERENCES gpus(id),
    -- RAM
    ram_total_gb        SMALLINT,
    ram_velocidade_mhz  INT,
    ram_tipo            VARCHAR(10),
    -- Armazenamento
    storage_livre_gb    INT,
    storage_total_gb    INT,
    -- DirectX
    directx_versao      VARCHAR(10),
    data_scan           TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_scans_usuario ON scans_pc (usuario_id);
CREATE INDEX idx_scans_cpu_id  ON scans_pc (cpu_id);


-- ================================================================
-- 9. RESULTADO DAS COMPARAÇÕES
-- ================================================================
CREATE TABLE comparacoes (
    id                  SERIAL PRIMARY KEY,
    scan_id             INT  REFERENCES scans_pc(id),
    app_id              INT  REFERENCES aplicativos(id),
    nivel               VARCHAR(20),    -- ABAIXO_MINIMO | MINIMO | RECOMENDADO | ACIMA
    score_cpu           SMALLINT,       -- 0-100
    score_gpu           SMALLINT,
    score_ram           SMALLINT,
    score_storage       SMALLINT,
    score_geral         SMALLINT,
    detalhes            JSONB,          -- json detalhado dos gaps
    realizado_em        TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_comp_scan ON comparacoes (scan_id);
CREATE INDEX idx_comp_app  ON comparacoes (app_id);


-- ================================================================
-- VIEW ÚTIL: resumo de compatibilidade
-- ================================================================
CREATE OR REPLACE VIEW vw_compatibilidade AS
SELECT
    c.id,
    sp.cpu_modelo   AS pc_cpu,
    sp.ram_total_gb AS pc_ram,
    sp.gpu_modelo   AS pc_gpu,
    a.nome          AS aplicativo,
    c.nivel,
    c.score_geral,
    c.realizado_em
FROM comparacoes c
JOIN scans_pc   sp ON sp.id = c.scan_id
JOIN aplicativos a ON a.id  = c.app_id;
