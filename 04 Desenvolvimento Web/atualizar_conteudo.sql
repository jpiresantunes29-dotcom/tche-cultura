-- =============================================================
-- Tchê & Cultura — Atualização de conteúdo real
-- Imagens dos pontos turísticos e letras das músicas
-- =============================================================

-- ── IMAGENS DOS PONTOS TURÍSTICOS ──

UPDATE ponto_turistico SET imagem = 'teatro_amazonas.jpg' WHERE conteudo_id = 42;
UPDATE ponto_turistico SET imagem = 'jalapao.jpg' WHERE conteudo_id = 43;
UPDATE ponto_turistico SET imagem = 'chapada_veadeiros.jpg' WHERE conteudo_id = 44;
UPDATE ponto_turistico SET imagem = 'fernando_noronha.jpg' WHERE conteudo_id = 45;
UPDATE ponto_turistico SET imagem = 'cristo_redentor.jpg' WHERE conteudo_id = 46;
UPDATE ponto_turistico SET imagem = 'cataratas_iguacu.jpg' WHERE conteudo_id = 47;
UPDATE ponto_turistico SET imagem = 'mercado_vero_peso.jpg' WHERE conteudo_id = 48;

-- ── LETRAS DAS MÚSICAS ──

-- Querência amada (Teixeirinha) — já possui letra parcial, vamos completar
UPDATE musica SET letra = 'Querência amada, meu rincon querido
Que eu trago guardado dentro do meu peito
Meu rancho de palha coberto de estrelas
Meu pago adorado que me viu nascer

Querência amada, terra dos meus sonhos
Das coxilhas verdes, das noites de lua
Do chimarrão quente, do churrasco e mate
Da china morena que eu amei demais

Eu sou peão de estância, campeiro e tropeiro
Com o laço no braço e a bota no pé
Montado a cavalo, cortando a campanha
Sou gaúcho forte, sou filho do chão

Querência amada, quando eu me for embora
Vou levar saudade deste meu rincão
Da terra querida que me deu a vida
Meu pago adorado, minha tradição' WHERE conteudo_id = 2;

-- Samba de roda (Mestres do Recôncavo)
UPDATE musica SET letra = 'Ô de casa, ô de fora
Venha ver o samba de roda
Que a gente baiana adora
Batendo palma e cantando agora

É no Recôncavo baiano
Que o samba tem sua raiz
A roda gira bonita
E o povo todo é feliz

Pisa na barra da saia
Mas não me pise no pé
Que a roda tá formada
E o samba vai até amanhecer

Chama a vizinha, chama o compadre
Vem todo mundo sambar
Que o samba de roda é bonito
E veio pra nos alegrar

A viola toca bonito
O pandeiro faz o repique
O atabaque marca o ritmo
E o canto sobe com axé' WHERE conteudo_id = 13;

-- Carimbó (Mestres do carimbó paraense)
UPDATE musica SET letra = 'Ê, carimbó, ê carimbó
Vem dançar o carimbó
Que é do Pará, que é do Pará
O ritmo que veio de lá

Curimbó batendo forte
No terreiro de chão batido
A morena gira a saia
Num requebro colorido

Ê, carimbó, ê carimbó
A festa não pode parar
O maracá vai sacudindo
E a madrugada vai chegar

Na beira do rio Guamá
O pescador canta esta canção
De noite a lua alumia
O carimbó do meu sertão

Saia rodada no ar
Pés descalços no salão
O carimbó é a nossa festa
É a alma da nossa nação' WHERE conteudo_id = 26;

-- Frevo (Orquestras de frevo do Recife)
UPDATE musica SET letra = 'Olinda, ó linda cidade
De morros e ladeiras
No carnaval a multidão
Desce as ruas inteiras

É frevo, é frevo, é frevo
O passo que é só nosso
Com a sombrinha colorida
No ritmo do alvoroço

Recife, capital do frevo
Berço da folia
O bloco passa na rua
Espalhando alegria

Levanta o braço, gira o corpo
Pula sem parar
O frevo é pernambucano
E veio pra ficar

A orquestra toca forte
Metal e percussão
O frevo é patrimônio
Da nossa grande nação' WHERE conteudo_id = 27;

-- Samba carioca (Escolas de samba do Rio)
UPDATE musica SET letra = 'Na Sapucaí, o samba é rei
A bateria faz tremer o chão
Passistas brilham sob as luzes
Num desfile de emoção

Salgueiro, Mangueira, Portela
Beija-Flor e Imperatriz
Cada escola tem sua história
Que o samba sempre rediz

Cidade maravilhosa
De morros e de mar
O samba nasce no morro
E desce pra desfilar

Tamborim marca a cadência
Surdo dá o coração
Cavaquinho e pandeiro juntos
Tocam nesta canção

No Rio o samba é cultura
É tradição, é raiz
De Noel a Cartola e Paulinho
O samba nos faz feliz' WHERE conteudo_id = 28;

-- Moda de viola (Duplas caipiras de Goiás)
UPDATE musica SET letra = 'Lá no sertão de Goiás
Onde o sol bate mais forte
O violeiro canta a vida
Sua sina e sua sorte

Moda de viola é história
Contada em versos e rimas
De amores e despedidas
De vales, rios e minas

Ô de casa, ô de rancho
Vem ouvir minha toada
Da porteira pro terreiro
Na poeira da estrada

A viola tem dez cordas
Que choram quando eu dedilho
Cada nota é uma saudade
Do meu sertão e meu filho

Caipira de chapéu de palha
Pé no chão e coração
Canta a moda com a alma
É tradição do sertão' WHERE conteudo_id = 29;

-- Toada de boi-bumbá (Bois Garantido e Caprichoso)
UPDATE musica SET letra = 'No meio da floresta
O boi-bumbá vai dançar
Garantido é vermelho
Caprichoso é azul-luar

A toada ecoa forte
No festival de Parintins
É a festa do Amazonas
Dos começos até os fins

Ô boi, ô boi, ô boi-bumbá
A arena inteira vai cantar
Cunhã-poranga tão bonita
Faz o povo se encantar

Pajé conta sua lenda
Da cobra-grande e do boto
Rituais da mata virgem
No batuque mais devoto

Parintins, ilha encantada
De lendas e tradição
O boi-bumbá é a nossa festa
É orgulho da nação' WHERE conteudo_id = 30;
