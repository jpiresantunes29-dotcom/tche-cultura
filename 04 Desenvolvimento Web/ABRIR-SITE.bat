@echo off
title Tche e Cultura - abrir o site
color 0A
chcp 1252 >nul

echo.
echo  ===========================================================
echo    TCHE ^& CULTURA - abrindo o site
echo  ===========================================================
echo.

REM ---------------------------------------------------- 1) Achar o XAMPP
set XAMPP=
for %%D in (C D E F) do if exist "%%D:\xampp\apache\bin\httpd.exe" set XAMPP=%%D:\xampp
if "%XAMPP%"=="" (
  echo  [ERRO] Nao encontrei o XAMPP.
  echo.
  echo  Instale o XAMPP em https://www.apachefriends.org
  echo  e rode este arquivo de novo.
  echo.
  pause
  exit /b 1
)
echo  [1/5] XAMPP encontrado em: %XAMPP%

REM ---------------------------------------------------- 2) Ligar Apache e MySQL
tasklist /fi "imagename eq httpd.exe" 2>nul | find /i "httpd.exe" >nul
if errorlevel 1 (
  start "" /min "%XAMPP%\apache\bin\httpd.exe"
  echo  [2/5] Apache ligado.
) else (
  echo  [2/5] Apache ja estava ligado.
)

tasklist /fi "imagename eq mysqld.exe" 2>nul | find /i "mysqld.exe" >nul
if errorlevel 1 (
  start "" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
  echo  [3/5] MySQL ligado.
) else (
  echo  [3/5] MySQL ja estava ligado.
)

echo        Aguardando os servicos subirem...
timeout /t 8 /nobreak >nul

REM ---------------------------------------------------- 3) Copiar o site para o htdocs
set DESTINO=%XAMPP%\htdocs\tche
if not exist "%DESTINO%" mkdir "%DESTINO%"
xcopy "%~dp0*" "%DESTINO%\" /E /I /Y /Q >nul 2>&1
if errorlevel 1 xcopy "%~dp0*" "%DESTINO%\" /E /I /Y /Q >nul
echo  [4/5] Site copiado para: %DESTINO%

REM ---------------------------------------------------- 4) Instalar o banco de dados
where curl >nul 2>&1
if errorlevel 1 goto SEMCURL

curl -s "http://localhost/tche/index.php?api=status" | find "\"banco\":true" >nul
if errorlevel 1 (
  echo        Instalando o banco de dados...
  curl -s "http://localhost/tche/index.php?api=instalar" >nul
  timeout /t 3 /nobreak >nul
  if exist "%XAMPP%\mysql\bin\mysql.exe" (
    "%XAMPP%\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 tche_cultura < "%DESTINO%\atualizar_conteudo.sql" 2>nul
  )
  echo  [5/5] Banco instalado com as imagens e letras.
) else (
  echo  [5/5] Banco de dados ja estava instalado.
)
goto ABRIR

:SEMCURL
echo  [5/5] Abra o site e clique em "Instalar banco de dados" se ele pedir.

:ABRIR
echo.
echo  ===========================================================
echo    PRONTO! Abrindo no navegador...
echo  ===========================================================
echo.
echo    Endereco: http://localhost/tche/
echo.
echo    Contas de teste - senha: Tche@2026
echo      admin@email.com    (administrador)
echo      ana@email.com      (estudante)
echo      carlos@email.com   (docente)
echo      marta@email.com    (pesquisadora)
echo      joana@email.com    (publico em geral)
echo.
start "" "http://localhost/tche/"
echo  Pode fechar esta janela.
echo.
pause
