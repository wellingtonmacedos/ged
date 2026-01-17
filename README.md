# GED Institucional

Sistema de Gestão Eletrônica de Documentos com controle de acesso por departamentos, trilha de auditoria encadeada, OCR e assinaturas eletrónicas / ICP-Brasil.

## Requisitos mínimos

- PHP 8.0+
- Extensões: `pdo`, `pdo_mysql`, `mbstring`, `gd`, `zip`
- Servidor web apontando para `public/index.php`

## Instalação rápida

1. Clone o repositório no servidor.
2. Acesse `install/install.php` pelo navegador.
3. Siga o assistente de instalação:
   - Verificação de requisitos
   - Configuração de banco de dados
   - Importação do `database/schema.sql`
   - Criação do usuário `SUPER_ADMIN`
4. Após a conclusão, acesse o sistema pela URL principal.

## Configuração

O arquivo `.env` é criado na raiz do projeto com as principais variáveis de ambiente (dados de conexão, timezone, etc.).

## Funcionalidades principais

- Organização de documentos por departamentos e pastas
- Upload e versionamento de PDFs
- Busca avançada por metadados e texto (OCR)
- Assinaturas eletrônicas e ICP-Brasil
- Relatórios institucionais (documentos, assinaturas, auditoria, OCR)
- Dashboard gerencial com KPIs
- Ações em lote (download ZIP e auditoria)
- Trilhas de auditoria com verificação de integridade

