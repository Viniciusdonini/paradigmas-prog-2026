# Módulo de Cursos, Turmas e Matrículas

Implementação do trabalho do segundo bimestre na base oficial
[paradigmas-prog-20262](https://github.com/rafaelrotto/paradigmas-prog-20262).

## Funcionalidades e arquitetura

- CRUD REST de `Course`, com nome obrigatório e descrição opcional.
- Relação 1-N: `Course::classrooms()` e `Classroom::course()`.
- Relação N-M: `Classroom::users()` e `User::classrooms()`, usando `classroom_user`.
- POST para incluir/remover um usuário de uma turma sem duplicar vínculos.
- Autenticação `auth:sanctum`, validação por `FormRequest` e respostas por `JsonResource`.
- `CourseController -> CourseService extends BaseService -> CourseRepository extends BaseRepository`.
- Matrículas seguem `ClassroomController -> ClassroomService -> ClassroomRepository`.
  O service escolhe a operação e delimita a transação; o repository acessa o banco.

## Executar localmente

Requisitos: PHP 8.2+ (recomendado 8.3), Composer e MySQL ou SQLite.
Habilite as extensões usuais do Laravel, incluindo `mbstring`, `fileinfo` e o
PDO do banco escolhido. Os testes usam SQLite em memória por padrão.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

No PowerShell, use `Copy-Item .env.example .env` para copiar o arquivo.
Para MySQL local, configure `.env` com o banco previamente criado:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paradigmas-prog
DB_USERNAME=root
DB_PASSWORD=
```

Para SQLite, crie um arquivo vazio `database/database.sqlite`, defina
`DB_CONNECTION=sqlite` e `DB_DATABASE` com o caminho absoluto desse arquivo.

```bash
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve --port=8010
```

API: `http://localhost:8010`. A base registra as rotas **sem prefixo `/api`**.
Swagger UI: `http://localhost:8010/api/documentation`.
O seeder original cria o usuário local `admin@admin.com`, senha `admin123`.
Use-o apenas para desenvolvimento.

### Docker

O `.env.example` já aponta para o serviço `mysql` do Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan l5-swagger:generate
```

Aguarde a instalação das dependências e a inicialização do MySQL antes das
migrations. A API fica em `http://localhost:8020`.

## Endpoints

Faça `POST /login` com `email` e `password`, copie `token` e envie os headers
`Authorization: Bearer SEU_TOKEN` e `Accept: application/json`.
Nos POST/PUT/PATCH, envie também `Content-Type: application/json`.

| Método | Rota | Operação |
| --- | --- | --- |
| GET | `/courses?name=Sistemas` | Listar/filtrar cursos e suas turmas |
| POST | `/courses` | Criar curso |
| GET | `/courses/{id}` | Exibir curso e suas turmas |
| PUT/PATCH | `/courses/{id}` | Atualizar curso; campos omitidos são preservados |
| DELETE | `/courses/{id}` | Excluir curso sem turmas |
| GET | `/classrooms?course_id=1&name=Turma` | Listar/filtrar turmas, incluindo curso e usuários |
| POST | `/classrooms` | Criar turma vinculada a um curso |
| GET | `/classrooms/{id}` | Exibir turma, curso e usuários |
| PUT/PATCH | `/classrooms/{id}` | Atualizar turma: nome, vagas e curso obrigatórios |
| DELETE | `/classrooms/{id}` | Excluir turma e seus vínculos |
| POST | `/classrooms/{id}/users` | Incluir/remover usuário da turma |

### Criar um curso

`POST /courses`

```json
{
  "name": "Sistemas de Informação",
  "description": "Curso de graduação."
}
```

`name`: texto de 2 a 255 caracteres. `description`: opcional, aceita `null`,
até 5.000 caracteres. Use o `data.id` retornado como `course_id`.

### Criar uma turma

`POST /classrooms`

```json
{
  "name": "Turma A",
  "vacancies": 30,
  "course_id": 1
}
```

O curso deve existir. As vagas devem ser um número inteiro de 1 a 500.

### Matricular um usuário

`POST /classrooms/1/users`

```json
{
  "user_id": 2,
  "action": "attach"
}
```

O usuário precisa existir e pode ser cadastrado pelo CRUD `/users` da base.
Para remover, use a mesma rota com `"action": "detach"`.

A resposta é `200` com a turma em `data`, incluindo `course` e `users`.
Repetir `attach` não duplica a matrícula; repetir `detach` mantém o vínculo
ausente. Somente o usuário da turma informada é afetado. A transação bloqueia
a turma durante a operação, e um índice único protege o par
`(classroom_id, user_id)`. O campo `vacancies` continua sendo um dado da turma;
o enunciado não estabelece uma regra de limite de matrículas.

### Respostas e integridade

- Criação: `201`; consultas e atualizações: `200`; exclusão de curso/turma: `204`.
- Sem autenticação: `401`; registro inexistente: `404`; entrada inválida: `422`.
- Excluir curso com turmas: `409`. Transfira ou remova as turmas antes.
- Excluir usuário ou turma remove os vínculos por `ON DELETE CASCADE`,
  preservando as outras entidades. Curso com turmas tem `ON DELETE RESTRICT`.
- Relações são carregadas antecipadamente nas consultas. Resources condicionais
  evitam serialização recursiva e não expõem senhas dos usuários.

## Migrações e dados existentes

As migrations originais foram preservadas. As três novas criam `courses`,
adicionam `course_id` e criam `classroom_user`.

Se houver turmas antigas, a migração cria um único **Curso legado** e as
vincula a ele antes de tornar `course_id` obrigatório. Depois, transfira-as
para seus cursos reais pela atualização de turmas. Em banco vazio, nenhum
curso de transição é criado. O rollback das novas migrations remove os
relacionamentos e preserva as turmas originais.

## Testes

```bash
php artisan test
php artisan l5-swagger:generate
```

A suíte cobre CRUD, validações, autenticação, relações nos dois sentidos,
respostas com dados associados, matrículas repetidas, remoção isolada,
limpeza da pivô, restrições do banco, migração de turmas preexistentes e
rollback. Não é necessário subir MySQL para os testes locais padrão.

O workflow `.github/workflows/tests.yml` executa a suíte com SQLite e MySQL
8.4, além de validar a geração da documentação. Os resultados do GitHub
Actions ficam disponíveis após o envio dos commits ao fork.

## Entrega no fork

Publique em um **fork** do repositório oficial, preservando o histórico.
Em uma cópia local com os commits deste trabalho:

```bash
gh auth login
gh repo fork rafaelrotto/paradigmas-prog-20262 --remote=false
git remote rename origin upstream
git remote add origin https://github.com/SEU_USUARIO/paradigmas-prog-20262.git
git push -u origin master
```

Substitua `SEU_USUARIO` pelo seu login. Se já houver fork ou remotes,
aproveite os existentes. Confira os arquivos e os testes no GitHub e envie
`https://github.com/SEU_USUARIO/paradigmas-prog-20262` no Moodle.
