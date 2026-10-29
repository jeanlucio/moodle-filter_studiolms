# 🧪 Testes Automatizados

* **PHPUnit:** **60 testes** (239 asserções), rodando no CI nos Moodles 4.5, 5.0, 5.1, 5.2 e 5.3. A cobertura de linhas da lógica é de **98%** (350 de 356 linhas, medida em 29 de outubro de 2026 com Xdebug). O que sobra são seis linhas: o arquivo de upgrade vazio de uma primeira versão e alguns ramos defensivos.
* **Behat:** três features, 13 cenários: a barra do fórum (a fatia da turma, como ela acompanha as mensagens, o tamanho mínimo da turma, o aviso ao professor, uma marca na atividade errada e um professor que digita a marca num fórum novo), o mapa do curso e as regras do questionário (inclusive uma mudança feita depois pelo professor).
* **Casos que importam:** um nome como `A & B > 3` numa seção sob os dois valores de `formatstringstriptags`; o cache renovado só pelo evento que muda a contagem (cada evento foi conferido removendo o observador dele); um estudante com prazo estendido que vê o dele enquanto um colega vê o padrão; progresso que conta as atividades dentro de uma subseção.
* **Consultas:** uma linha de base do número de consultas de cada teste, que apontaria um laço que consulta uma vez por estudante ou por seção. Não apontou nada.
