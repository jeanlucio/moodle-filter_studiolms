# ✨ Funcionalidades

O **StudioLMS Widgets** é um filtro de texto. O professor digita uma marca curta em algum texto do curso, e quando a página é exibida a marca é trocada por um widget com dados ao vivo. Nada é guardado no texto além da marca, então o widget nunca fica desatualizado.

| Marca | Onde funciona | O que mostra |
|---|---|---|
| `[FORUMSTATS]` | A descrição de um fórum | Uma barra com quanto da turma participou naquele fórum |
| `[COURSEMAP]` | Qualquer texto de um curso | As seções do curso como um caminho, por data ou pelo progresso do próprio estudante |
| `[QUIZINFO]` | A descrição de um questionário | As regras do questionário como estão agora |

* 📊 **Participação no fórum (`[FORUMSTATS]`):** uma barra e duas linhas: quem participou e quem ainda não, com a porcentagem e a contagem ("6 de 9"). Participar é ter enviado ao menos uma mensagem (um tópico ou uma resposta) naquele fórum; mensagens apagadas e respostas privadas não contam. Conta os estudantes do curso (quem tem `mod/forum:replypost` e aparece nos relatórios de conclusão), e num fórum com grupos separados cada estudante vê a contagem dos próprios grupos. Uma turma menor que o mínimo (5 por padrão) fica escondida dos estudantes, para ninguém ser identificado, e o professor a vê com um aviso.
* 🧭 **Mapa do curso (`[COURSEMAP]`):** as seções do curso como etapas numeradas, cada uma com um link para a seção. A seção geral, as seções que o usuário não vê e as subseções ficam de fora. Por padrão a etapa atual é a que o formato do curso marca (o tópico destacado, ou a semana de hoje). Com `current=progress` as etapas seguem o que o usuário concluiu: uma seção está concluída quando toda atividade com regra de conclusão está, a primeira com algo pendente é a etapa atual, e uma seção sem atividade com regra de conclusão fica sem marca. Um curso com mais de 12 seções é mostrado em cartões, e `style=tiles` ou `style=steps` força um dos dois.
* ✅ **Regras do questionário (`[QUIZINFO]`):** cartões com o número de questões (só as questões de verdade: uma descrição não é questão), o tempo, as tentativas, a nota para passar, o método de avaliação (mostrado quando há mais de uma tentativa) e a data de fechamento. Cada pessoa vê as próprias regras: um estudante com prazo estendido ou mais tentativas vê as dele, e um colega vê as padrão.
* ⚡ **Leve para o site:** texto sem `[` é devolvido na hora, sem nenhuma consulta. As contagens do fórum são uma consulta agregada, guardada por cinco minutos e renovada quando uma mensagem é postada, apagada ou movida.
* 🔒 **Seguro por desenho:** o alvo (o fórum, o questionário ou o curso) vem sempre do lugar em que o texto é exibido, nunca do que se digita na marca. Visitantes e convidados não recebem widget, e uma marca onde não se aplica é simplesmente removida.
* ♿ **Acessível e com o tema:** os widgets usam marcação simples que sobrevive ao limpador de HTML do Moodle, com texto para o estado de cada etapa, e seguem o tema.
