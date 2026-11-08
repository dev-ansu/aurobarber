# AuroBarber — Documento de Contexto e Arquitetura

> **Conversa de Origem / Criação:** [Chat Inicial](conversation://807e76fa-2c65-4358-a2c3-d6bd08839383)  
> **Data de Atualização:** 08/11/2026  
> **Status:** 100% Online em Produção — Arquitetura Multi-Tenant & Multi-Unidades Ativa

---

## 1. Visão Geral
O **AuroBarber** é uma plataforma SaaS multi-tenant para barbearias, redes e salões, operando de forma 100% desacoplada e independente do Aurosoft LMS (Escola).

- **Repositório Git:** `https://github.com/dev-ansu/aurobarber.git` (branch `main`)
- **Pasta Local:** `c:\Users\ALUNO\Desktop\aurobarber`
- **Servidor VPS:** `2.25.75.35` em `/var/www/aurobarber`
- **Servidor Web:** FrankenPHP (Caddy engine) configurado em `/var/www/aurosoft/Caddyfile`
- **DNS / SSL:** Cloudflare Proxied para `barber.aurosoft.com.br`

---

## 2. Banco de Dados & Arquitetura Multi-Unidades
- **SGBD:** PostgreSQL na VPS
- **Database:** `aurobarber_db` (totalmente isolado do banco `aurosoft`)

### Tabelas Principais:
1. `barbershops`: Dados da unidade/filial (nome, slug, owner_id, parent_id, whatsapp, cor `#d97706`, plano).
2. `barbershop_user`: Tabela pivô que permite a um usuário/proprietário gerenciar múltiplas unidades/filiais.
3. `professionals`: Barbeiros escopados por unidade (`barbershop_id`), % de comissão e status.
4. `services`: Catálogo de serviços com escopo por filial (`barbershop_id`), preço e duração.
5. `appointments`: Agendamentos dos clientes, barbeiro, serviço e cálculo de comissão.
6. `users`: Usuários do sistema (`master_admin`, `owner`, `barber`, `receptionist`), implementando o contrato `HasTenants`.

---

## 3. Rotas e Endpoints em Produção

| Tipo | Rota | Descrição |
| :--- | :--- | :--- |
| **Landing Page** | `https://barber.aurosoft.com.br/` | Página de vendas e apresentação do SaaS AuroBarber |
| **Marinho - Matriz** | `https://barber.aurosoft.com.br/marinho` | Agendamento online da Unidade Centro do Marinho |
| **Marinho - Aldeota** | `https://barber.aurosoft.com.br/marinho-aldeota` | Agendamento online da Filial Aldeota do Marinho |
| **Vintage Club** | `https://barber.aurosoft.com.br/vintage-club` | Página de outro cliente SaaS (totalmente isolado) |
| **Painel de Gestão** | `https://barber.aurosoft.com.br/admin` | Redireciona para o tenant ativo no Filament |
| **Nova Unidade** | `https://barber.aurosoft.com.br/admin/new` | Cadastro de nova filial pelo próprio dono |
| **Config da Unidade** | `https://barber.aurosoft.com.br/admin/{tenant}/profile` | Edição dos dados da filial ativa |

---

## 4. Credenciais de Teste / Acesso

### A. Dono da Barbearia Marinho (Rede com 2 Unidades):
- **Login:** `marinho@barber.com.br`
- **Senha:** `marinho123`
- *Tem acesso às filiais "Centro" e "Aldeota", com seletor de unidade no topo.*

### B. Dono da Barbearia Vintage Club (Outro Cliente SaaS):
- **Login:** `rodrigo@vintageclub.com.br`
- **Senha:** `vintage123`
- *Tem acesso estritamente à sua barbearia (isolamento total garantido).*

### C. Super Administrador AuroBarber (Aurosoft Tecnologia):
- **Login:** `admin@aurobarber.com.br`
- **Senha:** `aurobarber123`
- *Tem acesso global a todas as redes e barbearias da plataforma.*

---

## 5. Histórico de Decisões Técnicas
- O sistema utiliza **Filament Tenancy v5 nativo**, com resolução por `slug`.
- A relação entre usuário e barbearias é **Muitos para Muitos** (`barbershop_user`), permitindo que um mesmo proprietário gerencie 1, 2, 5 ou mais filiais sem precisar de logins separados.
- O recurso `BarbershopResource` é visível exclusivamente para o `master_admin`, garantindo que um cliente nunca veja a lista de outros concorrentes cadastrados na plataforma.
