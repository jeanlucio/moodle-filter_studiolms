# 🔗 Integrações

O filtro não precisa de mais nada. Os outros dois plugins da [família StudioLMS](https://jeanlucio.github.io/studiolms/) usam o filtro quando ele está lá; este plugin não os conhece.

| Plugin | Como usa o filtro |
|---|---|
| [`tiny_studiolms`](https://jeanlucio.github.io/moodle-tiny_studiolms/) (StudioLMS Editor) | Uma aba **Widgets** no diálogo insere as três marcas como blocos. Ela pergunta ao filtro quais widgets estão ligados e se o filtro está ativo no curso, e mostra um aviso quando não está. Sem o filtro a aba não aparece |
| [`local_studiolms`](https://jeanlucio.github.io/moodle-local_studiolms/) (StudioLMS Course Builder) | Ao gerar um questionário ele grava `[QUIZINFO]` na descrição, para as regras ficarem sempre em dia. Só faz isso onde o filtro está instalado, com o widget ligado e ativo no curso; senão grava os números uma vez |
