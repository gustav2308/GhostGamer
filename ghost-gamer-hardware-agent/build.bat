@echo off
REM ============================================================
REM  Ghost Gamer Hardware Agent -- gera GhostGamerAgent.exe
REM  Rode este arquivo clicando duas vezes nele, ou via:
REM      build.bat
REM  dentro da pasta ghost-gamer-hardware-agent\
REM ============================================================

echo.
echo === Ghost Gamer Hardware Agent - build do executavel ===
echo.

where python >nul 2>nul
if errorlevel 1 (
    echo [ERRO] Python nao foi encontrado no PATH.
    echo Instale o Python 3.10+ de https://www.python.org/downloads/
    echo e marque a opcao "Add Python to PATH" durante a instalacao.
    pause
    exit /b 1
)

echo [1/3] Instalando o PyInstaller ^(se ainda nao estiver instalado^)...
python -m pip install --quiet --upgrade pyinstaller
if errorlevel 1 (
    echo [ERRO] Falha ao instalar o PyInstaller. Verifique sua conexao com a internet.
    pause
    exit /b 1
)

echo.
echo Deseja gerar o executavel com janela de console visivel?
echo   [S] Com console  -- RECOMENDADO durante o desenvolvimento: uma janela
echo       preta fica aberta mostrando logs e qualquer erro, mais facil de
echo       depurar.
echo   [N] Sem console  -- para a versao final que o usuario comum vai usar:
echo       o Agent roda sem nenhuma janela aparecendo. So use depois de ja
echo       ter testado com console e confirmado que esta tudo funcionando,
echo       porque erros passam a ficar invisiveis.
echo.
choice /C SN /N /M "Gerar com console? (S/N): "
if errorlevel 2 (
    set MODO=--noconsole
    echo.
    echo [2/3] Gerando GhostGamerAgent.exe SEM console ^(--noconsole^)...
) else (
    set MODO=--console
    echo.
    echo [2/3] Gerando GhostGamerAgent.exe COM console ^(--console^)...
)

python -m PyInstaller --onefile %MODO% --name GhostGamerAgent agent.py
if errorlevel 1 (
    echo [ERRO] O PyInstaller nao conseguiu gerar o executavel. Veja o erro acima.
    pause
    exit /b 1
)

echo [3/3] Pronto!
echo.
echo O executavel foi gerado em:
echo     dist\GhostGamerAgent.exe
echo.
if "%MODO%"=="--console" (
    echo Para iniciar o Agent, de um duplo clique em dist\GhostGamerAgent.exe
    echo ^(uma janela preta vai ficar aberta mostrando que ele esta rodando --
    echo  feche essa janela, ou aperte Ctrl+C dentro dela, para encerrar^).
) else (
    echo Este .exe foi gerado SEM janela de console ^(--noconsole^). Ele vai
    echo rodar em segundo plano sem nenhuma janela aparecer. Para confirmar
    echo que esta funcionando, abra http://127.0.0.1:8765/health no navegador.
    echo Para encerrar, use o Gerenciador de Tarefas ^(procure por
    echo "GhostGamerAgent.exe" na aba Detalhes/Processos e clique em
    echo Finalizar tarefa^).
)
echo.
echo Copie dist\GhostGamerAgent.exe para:
echo     site-ghost-gamer\ASSETS\AGENT\GhostGamerAgent.exe
echo para que o botao "Baixar Ghost Gamer Agent" do site passe a funcionar.
echo.
pause
