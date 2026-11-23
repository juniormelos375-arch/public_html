
# F&J Artes Presentes — Catálogo Digital

Aplicação independente de catálogo de fotos, construída com PHP 8.2+, MySQL/MariaDB, HTML, CSS e JavaScript. Não utiliza agentes ou provedores de IA.

## Recursos
- Vitrine pública responsiva em grade.
- Filtro por categoria e tema (tema é um campo da categoria).
- Busca por nome/descrição.
- Visualização ampliada em modal.
- Seleção de fotos e download em lote em ZIP.
- Painel administrativo com login.
- CRUD de categorias e fotos.
- Upload/substituição/remoção de logo.
- Cadastro da empresa (nome, slogan, telefone, WhatsApp, e-mail, endereço, site e descrição).
- Exportação do banco SQL pelo painel e pacote ZIP de mídia.
- Importação/restauração do SQL pelo phpMyAdmin ou linha de comando.
- Instalação guiada e configuração via `config.php`.


## Prévia local (sem hospedagem e sem banco de dados)

Para visualizar o catálogo imediatamente, abra o arquivo `demo.html` em um navegador. Ele funciona offline e inclui artes ilustrativas para testar a aparência, a busca, os filtros, a ampliação e a seleção de itens. O botão de ZIP apenas demonstra a ação nesta prévia; o download real em lote funciona na aplicação conectada ao banco de dados.

### Abrir no computador
- Extraia o ZIP do projeto.
- Abra `demo.html` com Chrome, Edge, Firefox ou Safari.

### Abrir no celular
- Extraia o ZIP usando o gerenciador de arquivos do aparelho.
- Toque em `demo.html` e escolha abrir no navegador.
- Se o Android não permitir abrir o arquivo diretamente, transfira a pasta para um computador e execute um servidor local (instrução abaixo), acessando pelo navegador do celular na mesma rede Wi-Fi.

### Servidor local para testar a aplicação PHP
Com PHP 8.2+ instalado, abra o terminal na pasta do projeto e execute `php -S 0.0.0.0:8000`. No próprio computador, acesse `http://localhost:8000/demo.html`. Para testar as telas PHP administrativas e a vitrine real, também será necessário configurar um MySQL/MariaDB local e seguir a instalação. Para acessar pelo celular na mesma rede Wi-Fi, use o endereço IP local do computador, por exemplo `http://192.168.1.10:8000/demo.html` (substitua pelo IP real).

## Requisitos
- PHP 8.2 ou superior
- MySQL 5.7+ ou MariaDB 10.4+
- Extensões PHP: PDO, pdo_mysql, fileinfo, gd (recomendado), zip
- Apache com `mod_rewrite` (opcional; a aplicação também funciona sem URLs amigáveis)

## Instalação
1. Envie o conteúdo desta pasta para a raiz do domínio ou subdomínio.
2. Crie um banco MySQL/MariaDB e um usuário com permissões.
3. Edite `config.php` com host, banco, usuário e senha.
4. Acesse `https://seu-dominio/install.php` e conclua a instalação.
5. Crie o primeiro administrador. O instalador bloqueia nova execução depois de instalado.
6. Entre em `/admin/`.
7. Por segurança, apague `install.php` após instalar.

## Estrutura
- `index.php`: vitrine pública
- `admin/`: painel
- `api/`: endpoints JSON
- `assets/`: CSS e JavaScript
- `uploads/`: fotos e logo
- `database/schema.sql`: estrutura do banco
- `config.php`: configuração local

## Exportar / migrar para outra hospedagem
1. No painel, acesse **Backup e portabilidade**.
2. Baixe o arquivo SQL e o ZIP de mídias.
3. Na hospedagem nova, envie o código do projeto.
4. Crie um banco novo e ajuste `config.php`.
5. Importe o SQL pelo phpMyAdmin ou `mysql -u usuario -p banco < backup.sql`.
6. Extraia o ZIP de mídias na raiz do projeto, preservando a pasta `uploads/`.
7. Ajuste permissões de escrita em `uploads/` (normalmente 755 ou 775).
8. Acesse o painel e confira o catálogo.

A aplicação não depende de Supabase, Firebase, serviços proprietários ou URLs de armazenamento externas. Para portabilidade integral, mantenha juntos o código, o dump SQL e a pasta `uploads/`.

## Segurança
- Senhas armazenadas com `password_hash`.
- Consultas PDO preparadas.
- Proteção CSRF nas ações administrativas.
- Validação de MIME real e extensão nos uploads.
- Limite de upload configurável em `config.php`.
- Arquivos PHP impedidos dentro de `uploads/` por `.htaccess` (Apache).
- Recomenda-se HTTPS, senha forte, backups frequentes e remoção de `install.php`.

## Observações de produção
O download em lote usa a extensão PHP ZipArchive. Se ela não estiver habilitada, solicite à hospedagem a ativação da extensão `zip`.


## Atualização do painel administrativo

A versão atual inclui um painel inicial com indicadores, fotos recentes e categorias em destaque; biblioteca visual de fotos; cartões de categorias; e a página **Identidade visual** para personalizar cores e a mensagem superior da vitrine.

Para visualizar o desenho do painel sem configurar banco de dados, abra `admin-preview.html`. Essa prévia usa dados fictícios e não salva alterações. O painel real fica em `admin/` e exige PHP e MySQL/MariaDB configurados. A tabela `settings` é criada automaticamente ao abrir a página de identidade visual; em instalações novas ela também consta em `database/schema.sql`.


## Cadastro de várias fotos

No painel, acesse **Fotos** e use a seção **Cadastro de várias fotos**. Selecione até 50 arquivos JPG, PNG, WEBP ou GIF por lote, confira as miniaturas, escolha uma categoria comum e, opcionalmente, informe um complemento para os títulos e uma descrição. O sistema usa o nome original de cada arquivo como título e mostra o progresso do envio. O limite individual de tamanho é definido por `max_upload_mb` em `config.php`; os limites globais do PHP (`upload_max_filesize`, `post_max_size` e `max_file_uploads`) também precisam permitir o lote escolhido.


### Revisar cada foto antes do cadastro em lote

Depois de selecionar as imagens, o painel mostra um cartão para cada arquivo. Antes de enviar, você pode alterar individualmente o título, a descrição e a categoria de cada foto. A categoria, a descrição padrão e o complemento de título do formulário servem como sugestões iniciais; os campos individuais podem ser ajustados livremente.


### Edição em lote da biblioteca

Na biblioteca de fotos do painel, marque uma ou mais imagens ou use “Selecionar todas”. Você pode aplicar uma categoria em conjunto e escolher como tratar as descrições: manter, substituir, acrescentar texto ao final ou limpar. A atualização é confirmada antes de ser aplicada e executada em transação no banco de dados. A edição em lote não altera os arquivos originais das imagens.


### Organização da biblioteca de fotos

A biblioteca agora inclui busca pelo título, filtro por categoria (incluindo fotos sem categoria), ordenação por data ou nome e alternância entre visualização em grade e lista. Os filtros funcionam juntos e mostram a quantidade de fotos exibidas. A seleção para edição em lote continua disponível nos dois modos.


## Lixeira de fotos

- Ao usar **Mover para lixeira**, a foto deixa de aparecer na vitrine, nos downloads e na biblioteca ativa, mas o registro e o arquivo são preservados.
- Em **Administração > Lixeira**, é possível restaurar uma foto ou excluí-la definitivamente.
- A coluna `photos.deleted_at` é adicionada automaticamente quando o catálogo acessa uma rota que utiliza fotos. Em instalações novas, ela já faz parte de `database/schema.sql`.
- Se preferir aplicar manualmente em um banco existente, execute `database/migration-photo-trash.sql` **somente se a coluna `deleted_at` ainda não existir**. Não execute a migração manual se o sistema já a adicionou automaticamente.
- A exclusão definitiva remove também o arquivo correspondente da pasta `uploads/`.


## Pedidos de personalização
A vitrine inclui o formulário `pedido.php`, que recebe dados do cliente, produto, quantidade, texto, detalhes e até cinco imagens de referência (JPG/PNG/WEBP, 8 MB cada). O painel administrativo possui a área **Pedidos**, com pesquisa, filtro por status, visualização de anexos, histórico de alterações e atalho para WhatsApp.

Em bancos existentes, execute `database/migration-orders.sql` uma única vez. Em instalações novas, as tabelas de pedidos já constam em `database/schema.sql`. A pasta `uploads/order-attachments/` deve ter permissão de escrita. Configure HTTPS em produção e faça backup regular.

Status disponíveis: Recebido, Em análise, Aguardando cliente, Aprovado, Em produção, Concluído, Entregue e Cancelado.
