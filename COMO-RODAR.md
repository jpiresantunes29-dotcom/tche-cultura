# Como rodar o site Tchê & Cultura (passo a passo simples)

Você vai precisar de um programa chamado **XAMPP**. Ele faz o computador funcionar como um servidor, que é o que o site precisa para abrir.

---

## Passo 1 — Baixar o projeto

1. Nesta página do GitHub, clique no botão verde **Code**.
2. Clique em **Download ZIP**.
3. Abra o arquivo baixado e **extraia** (descompacte) em qualquer lugar.

## Passo 2 — Instalar o XAMPP

1. Baixe em: https://www.apachefriends.org
2. Instale clicando em "Next" até o fim.

## Passo 3 — Colocar o site no lugar certo

1. Dentro do projeto que você extraiu, abra a pasta **04 Desenvolvimento Web**.
2. **Copie essa pasta inteira**.
3. Cole dentro de `C:\xampp\htdocs`.
4. Mude o nome da pasta copiada para **tche** (fica mais fácil de digitar).

No fim, deve existir: `C:\xampp\htdocs\tche\index.php`

## Passo 4 — Ligar o servidor

1. Abra o **XAMPP Control Panel**.
2. Clique em **Start** ao lado de **Apache**.
3. Clique em **Start** ao lado de **MySQL**.
4. Os dois devem ficar com fundo **verde**.

## Passo 5 — Abrir o site

1. Abra o navegador (Chrome, Edge, etc.).
2. Digite: **http://localhost/tche/**
3. Na primeira vez vai aparecer a mensagem **"Banco de dados não instalado"**.
4. Clique no botão **Instalar banco de dados** e espere terminar.
5. Pronto, o site abre!

---

## Para entrar no site (contas de teste)

A senha de todas é: **Tche@2026**

| E-mail | Quem é |
|---|---|
| admin@email.com | Administrador |
| ana@email.com | Estudante |
| carlos@email.com | Docente |
| marta@email.com | Pesquisadora |
| joana@email.com | Público geral |

---

## Deu problema?

- **Apache não liga:** algum outro programa está usando a porta. Feche o Skype ou outros servidores e tente de novo.
- **MySQL não liga:** reinicie o computador e tente de novo.
- **Página não abre:** confira se o endereço está certinho: `http://localhost/tche/` e se a pasta se chama `tche`.
- **Aparece erro de banco:** confira se o **MySQL** está verde no XAMPP.
- **Fotos das músicas ou dos pontos turísticos não aparecem:** abra o phpMyAdmin (`http://localhost/phpmyadmin`), clique no banco **tche_cultura**, vá em **Importar** e escolha o arquivo `atualizar_conteudo.sql` da pasta do site.

---

## Quero só dar uma olhada, sem instalar nada

Existe uma versão simples online, sem login e sem banco de dados:

**https://jpiresantunes29-dotcom.github.io/tche-cultura/**

---

## Onde fica o HTML?

Tudo fica em um arquivo só: **04 Desenvolvimento Web/index.php**.
O HTML começa na **linha 1180**.
