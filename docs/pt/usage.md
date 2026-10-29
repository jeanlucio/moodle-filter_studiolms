# 📖 Como Usar

## Escrevendo uma marca

Digite a marca no texto em que o widget deve aparecer, numa linha só dela. O texto do curso é filtrado quando a página é exibida, então não há nada a salvar além da marca.

* Na **descrição de um fórum**: `[FORUMSTATS]`
* Na **descrição de um questionário**: `[QUIZINFO]`
* Em qualquer **página, rótulo, descrição ou resumo de seção** de um curso: `[COURSEMAP]`

Com o **StudioLMS Editor** instalado você nem precisa digitar: a aba **Widgets** do diálogo dele insere a marca como um bloco (veja [Integrações](#integrations)).

## Opções do mapa do curso

Uma opção é escrita depois do nome, como `nome=valor`:

| Opção | Valores | O que faz |
|---|---|---|
| `current` | `date` (padrão), `progress` | `date`: a etapa atual é a que o formato do curso marca. `progress`: as etapas seguem o que o usuário concluiu |
| `style` | `auto` (padrão), `steps`, `tiles` | O visual do mapa. `auto` escolhe cartões para um curso com mais de 12 seções |

Por exemplo: `[COURSEMAP current=progress style=tiles]`. Um nome ou valor que não esteja na lista é ignorado, e uma marca com um nome que o filtro não conhece é removida.

## Onde ele precisa estar ligado

O filtro precisa estar ligado no curso (**Administração do site > Plugins > Filtros > Gerenciar filtros**, ou **Filtros** no curso). Ele é ligado na primeira instalação. Onde o filtro está desligado a marca aparece como foi digitada.

## O que o administrador pode ajustar

Em **Administração do site > Plugins > Filtros > StudioLMS widgets** cada widget pode ser desligado para o site todo, e o **tamanho mínimo da turma** da barra do fórum pode ser mudado (5 por padrão). Um widget desligado também sai da aba Widgets do editor.
