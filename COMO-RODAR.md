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

## Passo 4 — Ligar o servidor (detalhado)

O site só abre se o servidor estiver ligado. Pense assim: o **Apache** mostra o site e o **MySQL** guarda as informações. Os dois precisam estar ligados.

1. Aperte a tecla **Windows** do teclado e digite **XAMPP**.
2. Clique em **XAMPP Control Panel** para abrir.
   - Se o Windows perguntar "Deseja permitir que este aplicativo faça alterações?", clique em **Sim**.
3. Vai aparecer uma janela com uma lista de nomes (Apache, MySQL, FileZilla...). Você só vai usar dois.
4. Na linha do **Apache**, clique no botão **Start**.
   - Se der certo, o nome **Apache** fica com fundo **verde** e o botão muda para **Stop**.
5. Na linha do **MySQL**, clique no botão **Start**.
   - Se der certo, o nome **MySQL** fica com fundo **verde** e o botão muda para **Stop**.
6. Confira: **Apache** e **MySQL** com fundo verde. Pronto!

**Não feche a janela do XAMPP** enquanto estiver usando o site. Pode minimizar.

**Se algum não ficar verde:**
- Apareceu um aviso em vermelho dizendo que a **porta está em uso**? Outro programa está ocupando o espaço. Feche programas como Skype, Zoom ou outro servidor e tente de novo.
- Ainda não ligou? Reinicie o computador, abra o XAMPP de novo e clique em **Start**.

---

## Passo 5 — Abrir o site (detalhado)

1. Abra o navegador (Chrome, Edge ou Firefox).
2. Clique na barra de endereço lá em cima (onde você digita o site).
3. Digite exatamente isto e aperte **Enter**:

```
http://localhost/tche/
```

4. Na primeira vez, vai aparecer a mensagem **"Banco de dados não instalado"**. Isso é normal!
5. Clique no botão **Instalar banco de dados**.
6. Espere alguns segundos. Não feche a página.
7. Quando terminar, o site aparece com o **mapa do Brasil**. Pronto!

**Das próximas vezes**, é só ligar o Apache e o MySQL (Passo 4) e abrir `http://localhost/tche/`. Não precisa instalar o banco de novo.

**Para entrar com uma conta:**
1. Clique em **Entrar** no topo do site.
2. E-mail: `admin@email.com`
3. Senha: `Tche@2026`

**Se não abrir:**
- Apareceu "Não é possível acessar esse site"? Volte no XAMPP e veja se o **Apache** está verde.
- Apareceu "Not Found" (não encontrado)? A pasta está com o nome errado. Ela precisa se chamar **tche** e estar dentro de `C:\xampp\htdocs`.
- Apareceu erro de banco de dados? Veja se o **MySQL** está verde no XAMPP.

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
