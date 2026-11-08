# AuroBarber — Documento de Contexto e Arquitetura

> **Conversa de Origem / Criação:** [Chat Inicial](conversation://807e76fa-2c65-4358-a2c3-d6bd08839383)  
> **Data de Atualização:** 08/11/2026  
> **Status:** 100% Online em Produção — Multi-Tenancy & Agendamento Real com Bloqueio de Horários Ativo

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
3. `professionals`: Barbeiros escopados por unidade (`barbershop_id`), % de comissão e horários de trabalho.
4. `services`: Catálogo de serviços com escopo por filial (`barbershop_id`), preço e duração em minutos.
5. `appointments`: Agendamentos dos clientes gravados em tempo real no banco, profissional escolhido, cálculo automático de comissão (% e R$) e status (`confirmed`, `in_progress`, `completed`, `cancelled`).
6. `users`: Usuários do sistema (`master_admin`, `owner`, `barber`, `receptionist`), implementando o contrato `HasTenants`.

---

## 3. Fluxo de Agendamento Online Real (Pilar 1 Concluído)
1. **Frontend do Cliente (`/{slug}`):**
   * Interface mobile-first fluida com Alpine.js.
   * Seleção do serviço com indicação de duração e preço.
   * Escolha do barbeiro preferido ou opção **"Primeiro Disponível"**.
   * Carrossel de datas dos próximos 14 dias.
   * **Grade de Horários Dinâmica (`/{slug}/slots`):** Consulta a agenda do banco em tempo real e exibe apenas os horários realmente livres, bloqueando automaticamente conflitos com outros agendamentos e horários passados.
   * Captura de Nome e WhatsApp.
2. **Gravação & Persistência (`/{slug}/book`):**
   * Transação no banco com bloqueio pessimista (`lockForUpdate`).
   * Cálculo automático de término (`ends_at = scheduled_at + duration_minutes`).
   * Cálculo da comissão do barbeiro no momento da reserva.
   * Retorna tela de confirmação com protocolo oficial e botão direto para o WhatsApp da barbearia.
   * O agendamento aparece instantaneamente no Painel Administrativo em `/admin/{tenant}/appointments`.

---

## 4. Rotas e Endpoints em Produção

| Tipo | Rota | Descrição |
| :--- | :--- | :--- |
| **Landing Page** | `https://barber.aurosoft.com.br/` | Página de vendas e apresentação do SaaS AuroBarber |
| **Marinho - Matriz** | `https://barber.aurosoft.com.br/marinho` | Agendamento online real da Unidade Centro do Marinho |
| **Marinho - Aldeota** | `https://barber.aurosoft.com.br/marinho-aldeota` | Agendamento online real da Filial Aldeota do Marinho |
| **Vintage Club** | `https://barber.aurosoft.com.br/vintage-club` | Página de outro cliente SaaS (totalmente isolado) |
| **Painel de Gestão** | `https://barber.aurosoft.com.br/admin` | Redireciona para o tenant ativo no Filament |
| **Nova Unidade** | `https://barber.aurosoft.com.br/admin/new` | Cadastro de nova filial pelo próprio dono |
| **Config da Unidade** | `https://barber.aurosoft.com.br/admin/{tenant}/profile` | Edição dos dados da filial ativa |

---

## 5. Credenciais de Teste / Acesso

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
