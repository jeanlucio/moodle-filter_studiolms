# 🔐 Segurança e Privacidade

* **O alvo vem do lugar, nunca da marca.** O curso, o fórum e o questionário são tirados do contexto do texto que está sendo exibido, e o widget se recusa quando o usuário não pode ver aquele fórum ou questionário. O texto de uma marca não consegue apontar para outro curso ou atividade.
* **Opções por lista permitida:** o nome de uma opção só tem letras, um valor sem aspas só tem letras, números, sublinhado e hífen, as opções têm no máximo 200 caracteres, e um valor que não seja um dos esperados volta ao padrão.
* **A saída é escapada:** os templates imprimem com a marcação de escape, nunca a crua, e o nome de uma seção vira texto puro antes de chegar a eles.
* **Dado de outra pessoa não é mostrado.** A barra do fórum mostra só contagens da turma, respeita grupos separados e é escondida dos estudantes quando a turma é menor que o mínimo, para ninguém ser identificado. O mapa e as regras do questionário leem só o que pertence a quem está olhando: a própria conclusão e as próprias extensões.
* **Privacidade:** o filtro não cria tabela nem guarda preferência. O único cache dele guarda contagens agregadas de um fórum, e ele declara que não guarda dados pessoais (API de Privacidade).
* **Nenhum serviço externo, nenhuma IA, nenhuma biblioteca de terceiros.**
* **Auditado:** o plugin foi auditado em 29 de outubro de 2026 e não teve achado de segurança; os dois achados de qualidade de código (uma atividade dentro de uma subseção que o mapa de progresso deixava de fora, e um teste que não falhava sem o observador dele) foram corrigidos.
