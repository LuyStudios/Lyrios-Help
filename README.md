# Lyrios — Plataforma de Consultas Online de Psicologia

Plataforma completa em **PHP + MySQL** para ligar pacientes e psicólogos por videochamada, com contas separadas, marcação de consultas, pagamentos com comissão automática para a plataforma, e painel de administração total.

## Funcionalidades

- **Pacientes**: registo, login, marcar consulta com psicólogo/serviço, pagamento (simulado), histórico de consultas, atividades, perfil.
- **Psicólogos**: registo (aguarda aprovação do admin), agenda de consultas, marcar como concluída/cancelar, atividades, perfil profissional (especialidade, preço, biografia).
- **Videochamadas**: sala privada por consulta usando Jitsi Meet (gratuito, sem necessidade de servidor próprio de sinalização). Cada consulta gera uma sala única.
- **Pagamentos**: ao marcar a consulta, o valor é dividido automaticamente entre a plataforma (percentagem configurável) e o psicólogo.
- **Admin**: vê e gere tudo — utilizadores (aprovar/bloquear/eliminar), consultas, pagamentos, serviços (pode adicionar/desativar novos serviços), parceiros, mensagens de contacto e a percentagem de comissão da plataforma.
- **Páginas institucionais**: Início, História/Sobre, Serviços, Apoios, Parceiros, Contactos (com formulário que grava na base de dados).

## Pagamentos: Multicaixa Express, RedoPay e Wesi

A plataforma tem uma arquitetura de gateways de pagamento plug-and-play (pasta `gateways/`). Cada gateway implementa a mesma interface (`iniciarPagamento`, `verificarEstado`, `validarWebhook`), por isso é fácil adicionar mais no futuro.

**Estado de cada integração:**
- **Multicaixa Express** — implementada seguindo a estrutura real e documentada publicamente do GPO da EMIS (habitualmente acedido através de um provedor certificado como o vPOS). Precisas de preencher o `POS ID`, o `Cartão de Supervisor` e o `Token` fornecidos pela EMIS/provedor.
- **RedoPay** e **Wesi** — não encontrámos documentação pública oficial da API destes dois provedores. Construímos os adaptadores (`gateways/RedoPayGateway.php` e `gateways/WesiGateway.php`) seguindo o padrão REST mais comum do mercado (POST em JSON, autenticação Bearer, webhook assinado), **prontos a ligar assim que contactares os provedores e obtiveres a documentação oficial**. Terás de ajustar os nomes exatos dos campos nesses dois ficheiros (estão assinalados com comentários `TODO`).

**Como configurar:** entra como admin e vai a **Métodos de Pagamento**, ativa o(s) gateway(s) que já tiveres credenciais para usar, e preenche os campos (URL da API, chaves, POS ID, etc.). Cada gateway mostra o URL exato do webhook que deves configurar no painel do respetivo provedor.

**Como funciona o fluxo de pagamento:** ao marcar uma consulta, o paciente escolhe o método de pagamento entre os que o admin ativou. A consulta nasce com estado "pendente" e só passa a "confirmada" quando o pagamento é efetivamente confirmado — seja de forma instantânea (Multicaixa Express, após o cliente confirmar na app), por redirecionamento (RedoPay/Wesi, se usarem checkout hospedado), ou por notificação assíncrona (webhook em `/pagamentos/webhook.php`).

**Método "Pagamento Simulado"**: continua disponível e ativo por defeito, para testares a plataforma sem precisares de nenhuma credencial real.

## Segurança

- **CSRF**: todos os formulários da plataforma (login, registo, contacto, ações de admin, perfis, marcação de consultas) têm um token CSRF único por sessão, verificado em cada submissão.
- **Proteção contra força bruta**: após 5 tentativas de login falhadas, a conta fica bloqueada durante 15 minutos.
- **Sessões seguras**: cookies com `HttpOnly`, `SameSite=Lax` e `Secure` (quando em HTTPS); o ID de sessão é regenerado após cada login, para prevenir fixação de sessão.
- **Cabeçalhos HTTP de segurança**: `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`, `Referrer-Policy`.
- **SQL Injection**: todas as queries usam *prepared statements* (PDO) — nunca há concatenação direta de dados do utilizador em SQL.
- **XSS**: toda a saída de dados do utilizador passa pela função `escape()` (equivalente a `htmlspecialchars`).
- **Proteção de pastas sensíveis**: `.htaccess` bloqueia o acesso direto às pastas `config/`, `sql/`, `gateways/`, `includes/`, e impede a execução de PHP dentro de `uploads/` (proteção contra upload de scripts maliciosos).
- **Webhooks de pagamento assinados**: cada notificação recebida de um gateway só é aceite se a assinatura (HMAC-SHA256, calculada com a chave secreta configurada no admin) for válida — impede que alguém simule um pagamento chamando o URL diretamente.
- **Registo de eventos de segurança**: logins falhados, bloqueios de conta e webhooks inválidos ficam registados em `/admin/seguranca.php`.
- **Passwords**: guardadas com `password_hash` (bcrypt), nunca em texto simples; o registo exige mínimo de 8 caracteres com letras e números.

Se atualizares uma instalação já existente (em vez de começar do zero), corre o script `sql/atualizacao_pagamentos_seguranca.sql` uma única vez para adicionar as novas tabelas/colunas sem perderes os dados existentes.

## Verificação de psicólogos: fotos e certificados

- No **registo**, quem se inscreve como psicólogo tem de anexar obrigatoriamente um documento de qualificação profissional (diploma, cédula profissional, etc. — PDF, JPG ou PNG). A foto de perfil é opcional nesse momento.
- Em **Meu Perfil**, o psicólogo pode atualizar a foto a qualquer momento e enviar mais documentos.
- O **admin** revê tudo em `/admin/certificados.php`: pode ver cada documento (abre numa nova aba, protegido — só o próprio psicólogo ou o admin conseguem aceder), aprovar ou rejeitar (com motivo).
- Psicólogos com pelo menos um certificado aprovado aparecem com o selo "✓ Verificado" na lista de marcação de consultas dos pacientes.
- Os documentos ficam guardados fora de acesso público direto (`uploads/certificados/` bloqueado por `.htaccess`); só são servidos através de `documentos/ver.php`, que confirma sempre a permissão antes de mostrar o ficheiro.

Se estás a atualizar uma instalação já existente, corre também o script `sql/atualizacao_v3_certificados_chat.sql` e depois `sql/atualizacao_v4_chamadas_chat.sql`, uma vez cada, para criares as tabelas/colunas novas sem perderes os dados que já tens.

## Mensagens: texto e áudio entre paciente e psicólogo

- Paciente e psicólogo só podem conversar depois de terem pelo menos uma consulta marcada entre si (proteção de privacidade).
- Cada um tem uma página **Mensagens** (lista de conversas, com pré-visualização da última mensagem e contador de não lidas) e uma janela de **Chat** (texto + gravação de áudio).
- O áudio é gravado diretamente no navegador (API MediaRecorder) e enviado para o servidor; é guardado fora de acesso público direto e só reproduzido através de um endpoint que confirma que quem pede pertence mesmo àquela conversa.
- As mensagens novas chegam por *polling* automático (verificação a cada poucos segundos) — não é necessário recarregar a página.
- Há também um atalho direto da sala de videochamada para a conversa por mensagens com a mesma pessoa.

## Chamadas a partir do chat (a qualquer momento)

Além da videochamada da consulta marcada (`chamada/sala.php`, disponível à hora agendada), cada conversa tem agora a sua **própria sala privada e permanente**, acessível a qualquer momento através do botão **"Chamar"** no topo do chat.
- Paciente e psicólogo podem ligar-se um ao outro sempre que precisarem, sem esperar pela hora marcada.
- Ao iniciar uma chamada, fica automaticamente registada uma mensagem no chat com um botão "Entrar na chamada", para a outra pessoa ver assim que abrir a conversa (evita repetir o aviso se recarregares a página dentro de 2 minutos).
- A sala é única por conversa (mesmo par paciente + psicólogo), por isso ambos entram sempre no mesmo sítio.

**Nota sobre gravação de áudio:** por norma dos navegadores, o microfone só funciona em páginas servidas por **HTTPS** ou em `localhost`. Em ambiente local (XAMPP) normalmente funciona sem problema porque é `localhost`; em produção, precisas de certificado SSL para o botão de gravar áudio funcionar.

## Correção de links partidos / CSS não carrega

Se estiveres a aceder ao site através de uma subpasta (ex: `http://localhost/mindcare/`), o projeto já deteta isso automaticamente através do ficheiro `config/config.php` (constante `BASE_URL`), por isso o CSS e todos os links internos passam a funcionar independentemente da pasta onde colocares o projeto.

Se mesmo assim continuares a ter erro **"Not Found"** ao clicar nas páginas, confirma o seguinte:
- Estás a aceder pelo endereço correto, incluindo o nome da pasta, ex: `http://localhost/mindcare/index.php` (não `http://localhost/index.php` se o projeto estiver dentro de uma subpasta `mindcare`).
- O Apache (XAMPP) está a apontar o `DOCUMENT_ROOT` para a pasta `htdocs` onde colocaste o projeto.
- Se quiseres forçar manualmente o caminho, edita `config/config.php` e substitui o bloco de deteção automática por: `define('BASE_URL', '/mindcare');` (usa `''` vazio se o projeto estiver na raiz do site).

## Instalação

1. Copia a pasta `mindcare/` para o teu servidor (ex: `htdocs` do XAMPP, ou hospedagem com PHP + MySQL).
2. Cria a base de dados importando o ficheiro `sql/schema.sql` (via phpMyAdmin ou linha de comando):
   ```
   mysql -u root -p < sql/schema.sql
   ```
3. Configura os dados de ligação em `config/database.php` (host, nome da BD, utilizador, password).
4. Abre no navegador: `http://teusite.com/criar_admin.php` — isto cria a conta de administrador com:
   - **Email:** admin@lyrios.com
   - **Password:** admin123
   
   Depois **apaga o ficheiro `criar_admin.php`** do servidor por segurança.
5. Acede a `http://teusite.com/index.php` para ver o site.

## Fluxo de utilização

1. Um psicólogo regista-se em `/auth/registar.php?tipo=psicologo` — a conta fica **pendente** até o admin aprovar em `/admin/utilizadores.php`.
2. Um paciente regista-se em `/auth/registar.php?tipo=paciente` e já pode entrar de imediato.
3. O paciente marca uma consulta escolhendo psicólogo, serviço, data/hora e método de pagamento — o sistema calcula automaticamente a comissão da plataforma (definida em `/admin/configuracoes.php`, por defeito 20%).
4. À hora marcada, tanto o paciente como o psicólogo entram na mesma sala através do botão "Entrar", que abre a videochamada.
5. O admin acompanha tudo: utilizadores, consultas, pagamentos, receita da plataforma, mensagens de contacto, e pode adicionar novos serviços a qualquer momento.

## Requisitos

- **PHP 7.2 ou superior** (compatível com PHP 7; usa PDO, password_hash, cURL e as extensões `fileinfo` e `gd`/`exif` para validar imagens)
- MySQL 5.7+ / MariaDB
- Ligação à internet no browser do utilizador (para carregar o Jitsi Meet na videochamada)
- Para os uploads de certificados (até 8MB) funcionarem, confirma no `php.ini` que `upload_max_filesize` e `post_max_size` estão definidos para pelo menos `10M`.

## Notas de segurança para produção

- Este é um sistema funcional de base. Antes de colocares em produção real, recomenda-se:
  - Ativar HTTPS (obrigatório para câmara/microfone funcionarem em muitos browsers).
  - Integrar um gateway de pagamento real (ex: Multicaixa Express, EMIS GPO, Stripe) em vez do pagamento simulado.
  - Adicionar validação/CSRF tokens nos formulários.
  - Rever permissões de upload de ficheiros/fotos.

## Atualização — Recomendações do sistema (lista de 23 itens)

Implementados nesta ronda:

| # | Item | Onde |
|---|------|------|
| 1 | Responsividade | Menu mobile, sidebar colapsável, tabelas com scroll, grelhas adaptadas |
| 2 | Modo noturno | Botão no topo (site público e áreas privadas), guardado em `localStorage` |
| 3 | Questionário | `paciente/questionario.php` + gestão em `admin/questionario.php` |
| 4 | Historial do paciente | `psicologo/historial_paciente.php` (consultas + respostas ao questionário) |
| 5 | Psicólogo escolhe dias disponíveis | `psicologo/disponibilidade.php` |
| 6 | Limite de 10 consultas/dia por profissional | Validado em `verificarDisponibilidadePsicologo()` |
| 7 | Mínimo de 45 min entre sessões | Idem |
| 8 | Chamada e áudio | Já existente (videochamada + mensagens de áudio) |
| 9 | Relatos de pacientes na História | Tabela `depoimentos` + `admin/depoimentos.php`, exibidos em `historia.php` |
| 10 | Remarcar consulta | `paciente/remarcar.php` |
| 11 | Status personalizável do psicólogo | Campo em `psicologo/perfil.php`, visível na busca e na marcação |
| 12 | Paciente avaliar o psicólogo | `paciente/avaliar.php` (sistema de estrelas) |
| 13 | Menu do psicólogo (nota, agenda, relatório, receita) | Sidebar completa + `psicologo/relatorio.php` |
| 14 | Retirar total investido | `psicologo/relatorio.php` (pedido) + `admin/levantamentos.php` (aprovação) |
| 15 | Consulta pendente até pagamento + confirmação do profissional | Fluxo de dupla confirmação: o pagamento confirma primeiro, o psicólogo confirma disponibilidade depois em `psicologo/agenda.php`, e só aí a chamada fica acessível |
| 16 | Confirmação de email | `auth/verificar_email.php` + reenvio em `auth/reenviar_verificacao.php` |
| 17 | Recuperar senha | `auth/recuperar_senha.php` + `auth/redefinir_senha.php` |
| 18 | Mostrar benefícios do modelo | `beneficios.php` |
| 19 | Perguntas frequentes | `perguntas_frequentes.php` |
| 20 | Oferta de consulta | Sistema de cupões (`admin/cupoes.php`, aplicável no checkout) |
| 21 | Links para redes sociais | Rodapé do site público |
| 22 | Limite de 16 anos para criar conta | Validado no registo (`data_nascimento`) |
| 23 | Impedir gravação/captura de ecrã | Ver nota abaixo |

**Nota importante sobre o item 23**: nenhum site consegue impedir 100% a gravação de ecrã — um utilizador pode sempre gravar com uma câmara externa ou software do sistema operativo, fora do controlo do browser. Implementámos o máximo tecnicamente sensato: uma **marca de água** com o nome do participante e data/hora sobreposta ao vídeo (torna qualquer gravação rastreável a quem a fez), bloqueio do menu de contexto (botão direito), e aviso nos termos de uso. É dissuasão e rastreabilidade, não bloqueio técnico absoluto.

Se estás a atualizar uma instalação já existente, corre por esta ordem (uma vez cada): `atualizacao_v5_avaliacoes_seguranca.sql` e depois `atualizacao_v6_recomendacoes.sql`.

## Atualização — Rebranding para Lyrios, registo em assistente por etapas, index renovado

- **Nome**: a plataforma passou a chamar-se **Lyrios** em todo o site, emails e documentação visível. A pasta do projeto também foi renomeada para `lyrios/`.
  - **Nota técnica**: o nome interno da base de dados (`mindcare`) e os ficheiros de migração antigos mantiveram-se inalterados por baixo do capô, para não quebrar instalações já existentes que sigam os passos anteriores deste README. Isto não é visível a ninguém — é só o nome interno da BD. Se preferires renomear também a base de dados, cria-a como `lyrios` e ajusta `DB_NAME` em `config/database.php`.

- **Registo em formato de assistente (estilo BetterHelp)**: `auth/registar.php` foi totalmente reconstruído como um assistente por etapas — uma pergunta de cada vez, com barra de progresso, chips de seleção múltipla, cartões de escolha única e transições suaves. Paciente e psicólogo têm fluxos diferentes:
  - **Paciente**: escolha de perfil → motivo da procura → experiência prévia em terapia → preferência de género do psicólogo → dados pessoais → foto → password.
  - **Psicólogo**: escolha de perfil → dados profissionais (especialidade/preço/biografia) → upload do certificado → dados pessoais → foto → password.
  - Toda a validação do servidor (PHP) que já existia foi mantida; o assistente é apenas uma nova interface por cima da mesma lógica segura do lado do servidor.

- **Página inicial renovada**: nova secção de estatísticas animadas (com dados reais da base de dados: psicólogos verificados, consultas realizadas, pacientes, avaliação média), secção de psicólogos em destaque (melhor avaliados), secção "Porquê a Lyrios", depoimentos, pré-visualização de FAQ, e uma chamada final com fundo em gradiente. Todas as secções têm animação de entrada suave ao fazer scroll (`IntersectionObserver`, sem bibliotecas externas).

- **Responsividade**: revista globalmente — todas as novas secções (assistente de registo, estatísticas, cartões de destaque) têm as suas próprias media queries para telemóvel e tablet, sem alterar nenhuma funcionalidade existente.

Se estás a atualizar uma instalação já existente, corre também `sql/atualizacao_v7_registo_assistente.sql` uma vez.

## Correções de compatibilidade entre dispositivos (chamadas e áudio)

**O que estava a falhar e porquê:**

1. **Chamadas de vídeo** — o iframe do Jitsi era carregado de forma simples (`<iframe src="...">`), o que faz com que telemóveis tentem abrir a app nativa do Jitsi Meet (deep-linking), interrompendo ou confundindo a chamada. Também a política de segurança (CSP) do site só permitia scripts do próprio domínio, o que bloqueava silenciosamente qualquer tentativa de usar a integração oficial do Jitsi.
   - **Correção:** passámos a usar a *Jitsi Meet External API* (a forma oficialmente suportada de incorporar o Jitsi), com `disableDeepLinking: true` e sem o popup de "instala a app". Atualizámos a CSP para permitir `https://meet.jit.si`. Isto deve resolver problemas de telemóvel-para-telemóvel e telemóvel-para-computador.
   - Também adicionámos redirecionamento automático de volta à plataforma quando a chamada termina, e a opção de cada participante escolher a câmara/microfone certos antes de entrar (útil em tablets com várias câmaras).

2. **Mensagens de áudio** — cada navegador grava num formato diferente (Chrome/Android → `webm`, Firefox → `webm`/`ogg`, Safari/iPhone/iPad/Mac → `mp4`). **O Safari não consegue reproduzir ficheiros `.webm` de todo**, por isso um áudio gravado num Android não tocava num iPhone.
   - **Correção:** o servidor agora tenta **converter automaticamente todos os áudios para MP3** (formato universal, toca em qualquer dispositivo) assim que são recebidos, usando o `ffmpeg`.
   - Também corrigimos o streaming do áudio para suportar corretamente pedidos parciais (*HTTP Range*), que o Safari exige para reproduzir áudio com fiabilidade.
   - O navegador que grava também passou a escolher explicitamente o melhor formato suportado, em vez de deixar ao acaso.

**Importante — requisito para a conversão de áudio funcionar 100%:** a conversão para MP3 precisa do **ffmpeg instalado no servidor** e da função `shell_exec` do PHP disponível (não desativada em `disable_functions`).
- **XAMPP/Windows (local):** instala o ffmpeg e adiciona-o ao PATH do sistema (reinicia o Apache depois).
- **Linux/hospedagem própria:** `sudo apt install ffmpeg` (Ubuntu/Debian) ou equivalente.
- **Hospedagem partilhada (cPanel, etc.):** nem sempre é possível instalar o ffmpeg ou ativar `shell_exec` — confirma com o teu provedor.
- **Se o ffmpeg não estiver disponível**, a plataforma continua a funcionar normalmente, mas um áudio gravado num Android/Chrome pode não tocar num iPhone/Safari (limitação do próprio Safari, não da plataforma). Isto é a única situação em que a compatibilidade não fica 100% garantida.

## Novo design — UI moderno, efeitos e fotos reais

**Efeitos adicionados:**
- **Contagem automática**: os números de estatísticas (psicólogos, consultas, pacientes, avaliação média) sobem de 0 até ao valor real quando entram no ecrã. Corrigi também um bug que já existia e impedia esta contagem de funcionar.
- **Slide automático**: os depoimentos na página inicial agora aparecem num carrossel que avança sozinho a cada 5 segundos, com pontos clicáveis para navegar manualmente.
- **Efeito de espelho/reflexo**: a imagem principal do topo e as fotos de pacientes nos depoimentos têm um reflexo subtil por baixo, como um chão de vidro — efeito clássico de design premium.
- **Transições suaves**: nova curva de animação (`cubic-bezier`) aplicada a botões, cartões e imagens; cartões "levantam-se" ao passar o rato; a imagem principal tem um efeito 3D subtil que se endireita ao passar o rato por cima.
- **Cartões flutuantes** sobre a imagem do topo (avaliação média, selo de verificado), com animação de flutuação suave e contínua.
- **Selo de confiança social**: avatares sobrepostos dos pacientes com depoimento, junto ao número total de pessoas que já usam a plataforma.

**Sobre as fotos de pessoas reais:** usei fotos de stock com licença Unsplash (gratuita para uso comercial) como *placeholder* nos 3 depoimentos de exemplo e na imagem do topo da página inicial — nenhuma delas é de um cliente real da Lyrios. Isto é importante para seres transparente: antes de lançares a plataforma a sério,
- Substitui os depoimentos de exemplo por relatos e fotos reais dos teus próprios pacientes, **sempre com o consentimento explícito deles**;
- Em `/admin/depoimentos.php` já podes fazer upload da foto real de cada pessoa (ou colar um URL), em vez de usar as fotos de exemplo;
- Usar fotos de stock apresentadas como se fossem clientes reais, de forma permanente, pode ser considerado enganoso — usa-as apenas como referência visual temporária.

Se estás a atualizar uma instalação já existente, corre `sql/atualizacao_v10_fotos_depoimentos.sql` uma vez para adicionares a coluna de foto aos depoimentos.

## Refação de design — sistema visual premium

Reescrevi por completo o sistema visual do projeto (`assets/css/style.css`), eliminando os padrões que davam ao site um aspeto "gerado por IA":

**O que foi removido:**
- Manchas de gradiente desfocadas (blobs) atrás do herói
- Texto em gradiente colorido nos títulos
- Cartões flutuantes com sombra pesada sobre a imagem
- Grelhas genéricas de "ícone em caixa colorida + título + parágrafo" repetidas em cada secção
- Carrossel de depoimentos em caixa branca com pontos coloridos

**O que entrou no lugar:**
- **Kicker tipográfico** discreto (texto pequeno, maiúsculas, com um travessão fino) em vez de "pills" coloridas
- **Processo numerado** ("01, 02, 03") com linha de ligação fina, em vez de 3 cartões de ícone
- **Lista editorial de benefícios** (ícone + título + texto em linha, com divisórias finas), em vez de grelhas de cartões repetidos
- **Citações em destaque com transição suave (crossfade)**, tipografia serifada itálica, sem caixa nem sombra pesada
- Cartões com borda fina e sombra muito discreta, elevando-se ligeiramente ao passar o rato (sem "saltar")
- Tipografia com escala fluida (`clamp()`), maior hierarquia entre títulos e texto
- Paleta refinada e unificada: alinhei todas as cores fixas no HTML (que antes usavam valores hexadecimal ligeiramente diferentes dos tokens do CSS) para bater certo com o novo sistema, em todas as páginas

**Onde se aplica:** como praticamente todo o projeto usa as mesmas classes partilhadas (`.card`, `.btn`, `.grid`, `.stat-card`, `table`, `.badge`, `.sidebar`, `.chat-*`, `.wizard-*`, `.form-group`), esta atualização eleva automaticamente **todas as páginas** — página inicial, páginas institucionais, áreas de paciente/psicólogo/admin, chat, chamadas e o assistente de registo — sem que nenhuma funcionalidade tenha sido alterada ou removida.

**Páginas reescritas por completo** (composição, não só estilo): página inicial (`index.php`), `historia.php`, `beneficios.php` (a que tinha mais "cheiro a template", com 9 cartões de ícone — passou a lista editorial).

**Páginas com afinação leve** (mesma estrutura, pequenos ajustes de consistência): `servicos.php`, `apoios.php`, `parceiros.php`, `contactos.php`, `perguntas_frequentes.php`.

## Efeitos premium — vidro, flutuação e microinterações

Adicionei uma camada de efeitos aplicada a **todo o site** (público e áreas privadas), via `assets/js/efeitos-premium.js` e o bloco correspondente no `style.css`:

- **Barra de progresso de scroll**: linha fina no topo da página que preenche à medida que se lê a página.
- **Navbar reativa**: encolhe e intensifica o efeito de vidro (blur) ao começar a rolar a página.
- **Botão flutuante de ação** ("Criar conta grátis"): aparece com uma animação suave depois de passar a secção inicial, com brilho pulsante discreto — só é mostrado a visitantes que ainda não têm conta.
- **Botão "voltar ao topo"**: aparece nos mesmos moldes, em vidro fosco.
- **Vidro (glassmorphism) real**: o cartão de credencial sobre a imagem do herói usa `backdrop-filter: blur()` autêntico, não apenas um fundo sólido.
- **Brilho a percorrer os botões** ao passar o rato, e **onda (ripple)** ao clicar — como em interfaces premium.
- **Brilho subtil a percorrer os cartões** ao passar o rato (mais discreto que nos botões).
- **Imagem do herói com revelação suave** ao carregar a página, e **leve inclinação 3D ao mover o rato** por cima dela (apenas em computadores com rato; desativado em toque e quando o utilizador tem "reduzir movimento" ativo no sistema).

Tudo respeita `prefers-reduced-motion` (utilizadores que desativam animações no sistema operativo não veem estes efeitos) e não interfere com nenhuma funcionalidade existente — é uma camada visual adicional, sem tocar em lógica de negócio.

Corrigi também um pequeno conflito técnico: elementos com as classes `card` e `reveal` em conjunto (muito comuns no site) tinham duas regras de transição a competir entre si, o que ia tornar o efeito de hover instantâneo em vez de suave nesses casos — já está unificado.

## Redesign premium — dashboards, busca, agendamento e chamadas

**Nota sobre os conectores mencionados (Figma, Canva, Brightdeck, HyperFrames, PromptedSite, Supermetrics):** verifiquei a sua disponibilidade, mas nenhum tem aplicação direta para editar código PHP/CSS já existente — são ferramentas para gerar ficheiros de design, apresentações ou vídeos separados, não para modificar diretamente um projeto de código em produção. Por isso, todas as melhorias abaixo foram implementadas diretamente no código-fonte, como pedido.

**Dashboard do paciente e do psicólogo:**
- Saudação personalizada consoante a hora do dia ("Bom dia/Boa tarde/Boa noite, Nome")
- Cartões de acesso rápido às ações mais usadas (Procurar Psicólogo, Marcar Consulta, Mensagens / Agenda, Mensagens, Relatório)
- **Próxima consulta em destaque**, num cartão com gradiente e botão de entrada direta na chamada (paciente)
- **Aviso de consultas à espera de confirmação** para o psicólogo, com contador e link direto para a agenda

**Busca de psicólogos:**
- Filtro por especialidade (chips clicáveis: Ansiedade, Depressão, Trauma, etc.)
- Ordenação (mais relevantes, melhor avaliados, preço mais baixo/alto) — feita em PHP após o cálculo da avaliação, sem expor a query a SQL Injection (continua tudo com prepared statements)

**Processo de agendamento:** deixou de ser um formulário único longo e passou a um **fluxo em 4 passos visuais** (psicólogo → data/hora → pagamento → confirmação), com barra de progresso e resumo final antes de submeter — sem alterar em nada os nomes dos campos nem a lógica de validação/pagamento do backend (confirmado campo a campo).

**Área de consultas online:** as salas de chamada (`chamada/sala.php` e `chamada/conversa.php`) passaram a um **ecrã imersivo em ecrã inteiro**, sem o menu completo do site a distrair — barra superior minimalista com o nome da outra pessoa, atalho para o chat e botão de sair. A lógica de segurança, a API do Jitsi e a marca de água anti-gravação mantêm-se exatamente iguais.

Nenhuma lógica de negócio, validação, tabela de base de dados ou fluxo de pagamento foi alterado nesta ronda — apenas a camada visual e de experiência de utilizador.

## Design inspirado na ASAC Construções

Analisei o site https://asacconstrucoes.co.ao/ e trouxe para a Lyrios a linguagem estrutural que dá àquele site a sua identidade corporativa forte — sempre adaptada ao contexto de saúde mental (nada de "ar de site institucional frio"):

- **Barra de contacto no topo** (acima do menu principal): email, localização e redes sociais — desaparece ao rolar a página (o menu principal fica fixo sozinho), e esconde-se em ecrãs muito pequenos.
- **Serviços numerados com imagem alternada** (01, 02, 03), tal como a ASAC apresenta "Construção Civil / Infraestruturas / Reabilitação" — na Lyrios tornou-se "Consulta Individual / Terapia de Casal / Acompanhamento Contínuo", cada um com fotografia própria.
- **Secção de estatísticas em ecrã cheio**, com imagem de fundo e sobreposição escura — os mesmos números que já existiam (consultas, psicólogos, pacientes, avaliação), agora também apresentados neste formato de maior impacto visual, mais adiante na página.
- **Checklist de diferenciais** ("Profissionais verificados", "Conversas protegidas", etc.) com marcas de visto, tal como a secção "Porquê ASAC?" do site de referência.
- **FAQ com imagem lateral e cartão de suporte** (telefone/email em destaque), em vez de uma lista de perguntas isolada.
- **Rodapé com formulário de contacto integrado** — frase de impacto + formulário (nome, email, mensagem) lado a lado com as colunas de links, que envia diretamente para o mesmo processamento de `contactos.php` já existente (sem duplicar lógica nenhuma).

**Imagens novas usadas:** 2 fotografias adicionais com licença Unsplash (gratuita para uso comercial), confirmadas uma a uma antes de usar — a mesma prática já seguida anteriormente no projeto.

Esta atualização toca sobretudo na página inicial (`index.php`) e nos templates partilhados (`header.php`, `footer.php`), por isso a barra de contacto e o novo rodapé aplicam-se automaticamente a todas as páginas públicas do site.

## Consistência ASAC aplicada a todas as páginas públicas

Completei a aplicação do sistema inspirado na ASAC a todo o site (anteriormente só tinha tocado na página inicial):

- **`servicos.php`**: deixou de ser uma grelha de cartões e passou ao formato numerado com imagem alternada (01, 02, 03...), um por cada serviço cadastrado na base de dados — funciona com qualquer número de serviços que o admin adicionar.
- **`historia.php`**: secção "Quem somos" reformulada com imagem lateral e checklist, e Missão/Visão/Valores como três cartões dedicados.
- **`apoios.php`**, **`perguntas_frequentes.php`**: receberam o cartão de suporte (`suporte-cartao`) com contacto direto em destaque.
- **`parceiros.php`**: novo CTA final "Queres tornar-te parceiro?".
- **`beneficios.php`**: novo bloco de checklist resumido antes do CTA final.
- **`contactos.php`**: o cartão lateral de contacto passou a usar o mesmo `suporte-cartao` escuro usado no resto do site, em vez de um cartão claro genérico.

Com isto, a barra de contacto no topo, o rodapé com formulário, e os componentes numerados/checklist/suporte estão agora presentes de forma consistente em todas as páginas públicas da Lyrios.

## Logótipo oficial, tipografia da marca e dados simulados (projeto académico)

**Logótipo:** integrei o ficheiro que forneceste (`assets/img/logo-lyrios.png`) em todo o site — recortei o ícone (círculo com cérebro, folha e mão) separado do texto, e gerei automaticamente as versões otimizadas (`logo-icone.png`, `logo-icone-grande.png`, `favicon.png`, `favicon-32.png`) para não pesar o carregamento das páginas. Aparece agora no menu principal, no rodapé, nas salas de chamada e como favicon do navegador.

**Tipografia da marca:** extraí a cor exata do texto "Lyrios" do teu ficheiro (`#4fd9ff`) e apliquei a fonte **Baloo 2** (a que melhor corresponde ao estilo arredondado e "cheio" das letras do teu logótipo) especificamente no nome da marca — no menu, rodapé e salas de chamada. O resto do site mantém a tipografia editorial (Fraunces + Plus Jakarta Sans) para os títulos e texto corrido, para não perder a legibilidade e o ar profissional já construído.

**Estilo de imagem com desfoque verde-azul:** criei um "halo" com gradiente radial nas cores exatas do logótipo (verde, azul-claro, azul-escuro) com desfoque, atrás da imagem principal do herói, das imagens dos serviços numerados, e da imagem da secção de perguntas frequentes — o mesmo efeito visual do teu ficheiro de marca, aplicado às fotografias do site.

**Missão, Visão e Valores:** os três ícones passaram a usar um distintivo circular com o mesmo gradiente cónico do logótipo (verde → azul-claro → azul-escuro), em vez de ícones simples numa cor sólida.

**Dados simulados da empresa (para o trabalho académico):** como confirmaste tratar-se de um trabalho escolar que pede a simulação de uma empresa com 4 anos de mercado, adicionei à página "A Nossa História" uma faixa de estatísticas simuladas com o mesmo efeito de contagem automática já existente no site: 4 anos de mercado, 120 projetos e parcerias, 18 feiras e eventos, 6 províncias com presença. Na página inicial, mantive os contadores ligados a **dados reais** da base de dados (psicólogos, consultas, pacientes, avaliação média), já que esses continuam a ser genuínos e continuam a crescer à medida que a plataforma for usada.

**Sobre os logótipos de empresas/instituições reais (Sonangol, BAI, BFA, Unitel, Africell, TPA, ZAP, Total, Pumangol, Girassol, etc.):** não os incluí, mesmo sendo um trabalho académico — usar marcas registadas de empresas ou instituições públicas reais para sugerir uma parceria inexistente constitui uma alegação falsa de endosso, independentemente do contexto ser escolar ou comercial. Em vez disso:
- Criei uma faixa "Confiado por" na página inicial com **nomes de empresas genéricos e fictícios** (Nexus Saúde, Vítae Group, Aurora Tech, etc.), em texto estilizado — cumpre o mesmo objetivo visual de mostrar confiança social, sem qualquer risco.
- Corrigi também um dado de exemplo antigo na base de dados que referia uma universidade pública real, substituindo por um nome fictício.
- A página **Parceiros** (`admin/parceiros.php`) continua totalmente funcional para adicionares parceiros reais (com logótipo próprio) assim que existirem parcerias genuínas — é só uma questão de os adicionares lá quando fizer sentido.
