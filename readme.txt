=== Skloogs MegaSena ===
Contributors: skloogs
Donate link: http://tec.skloogs.com/dev/plugins
Tags: loteria, mega-sena
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: 3.1.1

Este plugins permite mostrar os resultados da mega-sena aos usuários e algumas estatísticas.

== Description ==

_IMPORTANT_
Desenvolvimento abandonado. Não há suporte oferecido. Obrigado. O autor.



Note: For English language, read [FAQ](faq)

O Plugin Skloogs Megasena apresenta aos usuários de seu blog o último resultado da megasena,
recuperado do site da Caixa Econômica e várias estatísticas do jogo desde o primeiro sorteio,
inclusivo:

* todos os resultados (ordenados por dezenas ou na ordem de saída) desde o início da megasena
* frequência de saída das dezenas
* atraso de cada dezena (o último concurso onde apareceu)
* quantas vezes cada dezena apareceu numa sena, numa quina, numa quadra, assim como numa
combinação probabilística de sena/quina/quadra
* ganhos médio e total das senas, quinas e quadras

A partir da versão 3.1 estão também disponíveis gráficos baseados nas estatísticas acima,
gerados a cada novo sorteio e portanto poupando a CPU do servidor web.

Enfim, o plugin também sugere combinações de 6 até 15 números (configurável pelo administrador)
de números com menos/mais frequência de saída, maior/menor atraso, etc... e finalmente algumas
combinações completamente aleatórias.
  
A versão 3 corrige o alto impacto na base de dados do servidor das versões anteriores,
e as estatísticas ficam atualizadas assim que saír o resultado do último concurso, após as 21h.

Todos os dados oferecidos pelo plugin podem ser usados de várias formas:

* Nos posts, comentários e páginas fixas, usando 'tags' do tipo `[megasena 'xxx']`
* Como widgets nas barras laterais de seu blog

A aparência do plugin é definida por uma folha de estílo padrão, mas pode ser customizada usando
uma folha própria "style.css" colocada no diretório do plugin.

Previsto nas próximas versões:

* Adaptação mais simples da aparência do plugin, para 'leigos' :)
* Mais gráficos...
* Algumas sugestões?

= Suporte =

Este plugin está sendo suportado no modo "best effort".
Se você encontrar algum problema, descreve nos foruns do WordPress.org, ou comente
[no meu blog](http://tec.skloogs.com/dev/plugins/skloogs-megasena) e corrigirei quando puder.
Se você gostar do plugin e for usá-lo mesmo no seu blogue, pense em fazer uma doação, e lhe
darei um suporte prioritário sem dúvida (me deixe saber da doação por email ou no blog).

== Installation ==

* Na moda antiga ou em caso de problema:

1. Carrega a pasta `skloogs-megasena` na pasta `/wp-content/plugins/`;
1. Ative o plugin dentro do menu 'Plugins' do WordPress;

* Com as versões recentes do WordPress (2.7 e seguintes):

1. Use a instalação/upgrade do sistema

* Uso do plugin:
 
1. Adicione os Skloogs Widgets desejados (megasena, random, gains, suggest, graphs) nas barras laterais
1. Use `[megasena]` nos posts e/ou comentários para o último resultado;
1. Use `[megasena random]` para oferecer 6 números aleatórios;
1. Use `[quina random]` para oferecer uma quina aleatória;
1. Use `[lotofacil random]` para oferecer um lotofácil aleatório;
1. Use `[lotomania random]` para oferecer uma lotomania aleatória.
1. Use `[megasena suggest]` para oferecer várias sugestões de jogos.
1. Use `[megasena games]` para mostrar a lista de todos os resultados da megasena.
1. Use `[megasena gains]` para mostrar estatísticas de ganhos na megasena.
1. Use `[megasena numbers_graph]` para visualisar gráficos estatísticos sobre as dezenas

== Frequently Asked Questions ==

= Why isn't there an version of this plugin for my country? =

Basically because the "MegaSena" is the brazilian national lottery game and I haven't yet found
the time to study all lottery systems worldwide...

However, all text is natively english with a portuguese translation (brazilian portuguese).

= Você ainda planeja implementar mais no plugin? =

A imaginação não tem limites, meu caro!
Aceito sugestões (e doações...)

= O que você ganha com isso? =

Fama, talvez... E também a felicidade de saber que alguém pode ganhar milhões graças a mim...
Note que qualquer doação me motiva bastante também ;)

= O que você usa para desenvolver plugins? =

Este plugins foi desenvolvido num MacBook, com a plataforma Eclipse, e os testes estão feitos
localmente com um pacote XAMPP (MacOSX-Apache-MySQL-PHP) e remotamente em vários blogs. O
navegador principal usado para testar é o Safari, mas também faço testes no Firefox (no Mac e
no Windows) e no Internet Exploder (Windows).

= E se não funcionar? =

Se não funcionar com essa preocupação toda minha, ainda pode ser minha culpa... Às vezes, esqueço alguma
coisa no momento do _SVN commit_ e tudo fica uma calamidade. Se acontecer, você já sabe: desative
o plugin, apague, e reinstale... Até já aconteceu comigo :(

= Você fala mal o português, sabia? =

Sou francês :P Me dê uma mãozinha?

== Screenshots ==

1. Resultado do último sorteio
2. Resultados de todos os concursos
3. Resultados de todos os concursos (aba do mês aberta)
4. Sugestão aleatória
5. Estatísticas por número
6. Estatísticas gerais
7. Vista com vários módulos ativados

== Changelog ==

= 3.1.1 =
* Minor bug correction
* Atualização preço da megasena 

= 3.1.0 =
* Adicionando gráficos estatísticos...
* Correção parte estatísticas para integrar último jogo no cálculo
* Coloquei o arquivo temporário de resultados e os gráficos no diretório do plugin
(que deverá ter permissão de escritura) 
* Renomei a folha de estilo para "default-style.css": "style.css" será usada se exitir, senão
"default-style.css". Assim suas próprias folhas de estílos não serão mais sobrescritas nas atualizações.

= 3.0.8 =
* Recoloquei a informação sobre o número de concursos analisados
* Mudei o modo de recuperação de resultados
* Corrigi um problema com a hora de recuperação
* Tudo deve funcionar direitinho, espero eu... :(

= 3.0.7 =
* Esvaziamento completo das tabelas da megasena antes da atualização (TRUNCATE não fazia efeito)

= 3.0.6 =
* Adaptação README para visualizar changelog (aqui mesmo)
* Correção de bug que impedia a atualização das stats embora os resultados estavam atualizados...

= 3.0.5 =
* "Options" agora 100% compatíveis APIs WP/WPMU 2.7+ (testado no WPMU 2.8.1) 

= 3.0.4 =
* correção "stable" tag do readme.txt

= 3.0.3 =
* enfim resolvi o problema com as atualizações sucessivas do resultado pela Caixa Economica :D 

= 3.0.2 =
* o problema com a DB ainda continuava nos dias de sorteio =
* agora RESOLVIDO!! :) 

= 3.0.1 =
* pequena correção na lista de concursos

= 3.0 =
* reescrevi toda parte ligada ao acesso à base de dado. Agora o plugin é muito mais rápido e menos consumidor de DB 

= 2.1.3 =
* problema quando Max Numbers não é definido -> def.val 8.

= 2.1.2 =
* ugly bug in admin section... :(

= 2.1.1 =
* Possilbilidade de ter as opções da Megasena nas Configurações ou num menu independente

= 2.1 =
* hey!!! Did you say widgets? You got them!!

= 2.0.2 =
* Adicionei compatibilidade Facebook... Em breve, uma surpresa! ;)

= 2.0.1 =
* correção hosting time offset (estava invertido)

= 2.0 =
* adicionei menu de admin Skloogs/Megasena e opções + configuração de offset horário caso
seu provedor de hospedagem não esteja no Brasil 

= 1.3.3.2 =
* temporarily authorizing url_fopen to get data (limitation from some providers)

= 1.3.3.1 =
* minor correction =
* data initialization

= 1.3.3 =
* next reward value correction

= 1.3.2 =
* added global stats and folding sections in various tables

= 1.3.1 =
* minor bug correction (sorting stats)

= 1.3 =
* adicionei os atrasos

= 1.2 =
* adicionei a lista dos concursos.

= 1.1 =
* Correção de vários bogues, ligados a atualização excessiva da base de dados...

= 1.0.2 =
* correção para evitar duplicados na base de dados (DB Version 0.2)

= 1.0.1 =
* correção de pequeno bogue sobre a atualização da última megasena.

= 1.0 =
* Agora o plugin tá BONITO (bom, eu acho...). Oficializamos com a versão 1.0

= 0.3.1.1 =
* Security Patch para versões 0.3. ATUALIZAÇÃO IMPRESCINDÍVEL!! 

= 0.3.1 =
* Pequeno bogue com a tradução... Corrigido!

= 0.3 =
* A versão tão esperada, com statísticas e sugestões!!!

= 0.2 =
* Corrigi um problema de apresentação da lotomania (com firefox). Apresentação de 10 números por linha no máximo.

= 0.1.3 =
* Eheheh... agora quina, lotofacil, lotomania aleatórios também!!

= 0.1.2 =
* Estamos quase lá. Os dados de todos os sorteios já estão na DB, só falta analisar. Gestão de números aleatórios pronta.

= 0.1.1 =
* Esthetic update (ordered megasena)

= 0.1 =
* First version. DB version 0.1. Showing latest result from the megasena

= Bugs & Troubleshooting =

a: auto-discovered & corrected functional bug (generally minor)
m: minor bug
M: major bug
S: Suggestion

_Open Bugs_

* None.

_Closed Bugs_

* 20090819-M6: resolvi mudar alguns algoritmos de atualização das estatísticas, já que algumas coisas
não davam certo até a versão 3.0.7 ...
* 20090814-M5: Falta de atualização das estatísticas a partir da versão 3 
* 20090718-M4: ENFIM resolvi o problema das atualizações sucessivas da C.E.F.
A checagem do site da megasena é feita apenas a cada 15 minutos.
* 20090716-M3: Excesso de acessos à base de dados [evocado por Diego Lopes]
* 20090308-M2: bug por falta de init Max Numbers [Diego Lopes]
* 20090228-M1: Erro na página de admin -> tipo
* 20090228-S1: Leave Skloogs Options Menu in Settings section (default) [Diego Lopes]
* 20090131-m2: error in next reward value.
* 20090130-a2: table header misclosed.
* 20090130-a1: gain presentation bug
* 20090130-m1: sorting doesn't work in permalink pages (rewrite pb?).
 