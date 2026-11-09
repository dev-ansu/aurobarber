# Contexto do AuroBarber

O AuroBarber é um produto independente desenvolvido pela Aurosoft, que atua como Software House. Este repositório contém somente o produto de gestão para barbearias.

## Arquitetura

- Aplicação Laravel com painéis Filament.
- `Barbershop` é o tenant; os dados operacionais são vinculados a `barbershop_id`.
- O painel SaaS gerencia barbearias, suporte e licenciamento. O painel da barbearia contém agenda, equipe, serviços, clientes e financeiro.
- O licenciamento do produto é validado no tenant `Barbershop`.
- Backups locais podem incluir a base PostgreSQL e arquivos enviados. O envio opcional ao Google Drive é configurado por variáveis de ambiente.
- Contatos de clientes são protegidos por cifragem e acesso controlado/auditado.

## Desenvolvimento e validação

- Use `.env.example` como referência e configure segredos somente no ambiente local ou no gerenciador de segredos da implantação.
- Não armazene credenciais, chaves, dados de produção ou endereços de infraestrutura neste documento ou no repositório.
- Para executar os testes focados: `php artisan test`.
- Para formatar PHP: `vendor/bin/pint`.
- Faça backup antes de migrações em ambientes compartilhados e revise as migrações antes da implantação.
