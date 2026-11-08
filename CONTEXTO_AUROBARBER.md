# AuroBarber — Documento de Contexto e Arquitetura

> **Conversa de Origem / Criação:** [Chat Inicial](conversation://807e76fa-2c65-4358-a2c3-d6bd08839383)  
> **Data de Criação:** 08/11/2026  
> **Status:** 100% Online em Produção

---

## 1. Visão Geral
O **AuroBarber** é uma plataforma SaaS multi-tenant para barbearias e salões, operando de forma 100% desacoplada e independente do Aurosoft LMS (Escola).

- **Repositório Git:** `https://github.com/dev-ansu/aurobarber.git` (branch `main`)
- **Pasta Local:** `c:\Users\ALUNO\Desktop\aurobarber`
- **Servidor VPS:** `2.25.75.35` em `/var/www/aurobarber`
- **Servidor Web:** FrankenPHP (Caddy engine) configurado em `/var/www/aurosoft/Caddyfile`
- **DNS / SSL:** Cloudflare Proxied para `barber.aurosoft.com.br`

---

## 2. Banco de Dados
- **SGBD:** PostgreSQL na VPS
- **Database:** `aurobarber_db` (totalmente isolado do banco `aurosoft`)
- **Permissões:** Owner e permissões totais no schema `public` concedidas ao usuário da aplicação.

### Tabelas Principais:
1. `barbershops`: Dados da barbearia (nome, slug, whatsapp, tema/cor `#d97706`, plano de assinatura).
2. `professionals`: Barbeiros/colaboradores, percentual de comissão, horários e status.
3. `services`: Serviços oferecidos (ex: Degradê, Barboterapia, Combos), preço, duração e categoria.
4. `appointments`: Agendamentos dos clientes, profissional escolhido, cálculo automático de comissão e status.
5. `users`: Usuários do sistema (papéis: `master_admin`, `owner`, `barber`, `receptionist`).

---

## 3. Rotas e Endpoints em Produção

| Tipo | Rota | Descrição |
| :--- | :--- | :--- |
| **Landing Page** | `https://barber.aurosoft.com.br/` | Página de vendas e apresentação do SaaS AuroBarber |
| **Cliente / Slug** | `https://barber.aurosoft.com.br/{slug}` | Página pública da barbearia (Ex: `/marinho`) com agendamento via WhatsApp |
| **Painel de Gestão** | `https://barber.aurosoft.com.br/admin` | Painel administrativo Filament v5 |

---

## 4. Credenciais de Teste / Acesso
- **Dono da Barbearia (Marinho):**
  - **Login:** `marinho@barber.com.br`
  - **Senha:** `marinho123`
- **Super Administrador AuroBarber:**
  - **Login:** `admin@aurobarber.com.br`
  - **Senha:** `aurobarber123`

---

## 5. Histórico de Decisões Técnicas
- O sistema herdou o ecossistema Laravel 13 / Filament v5, mas **todas as migrações e middlewares escolares foram expurgados**.
- `ResolveTenantSchema` e o reporter de tickets de suporte foram removidos de `bootstrap/app.php`. O isolamento multi-tenant do AuroBarber funciona por relação `barbershop_id` e identificação por subdomínio/slug pública.
