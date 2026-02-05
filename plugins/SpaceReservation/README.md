# SpaceReservation Plugin

Plugin para o Mapa Cultural que permite reservas de espaços cadastrados com fluxo de aprovação pelo gestor.

## Funcionalidades

- **Calendário público de disponibilidade** - Visualização mensal das reservas no espaço
- **Solicitação de reservas** - Usuários com agente verificado podem solicitar reservas
- **Aprovação pelo gestor** - Gestor do espaço aprova ou rejeita solicitações
- **Notificações** - Sistema de notificações para solicitantes e gestores
- **Configurações flexíveis** - Capacidade, antecedência mínima/máxima, instruções

## Instalação

1. Copie a pasta `SpaceReservation` para o diretório `plugins/` do Mapa Cultural
2. Ative o plugin no arquivo de configuração `config/plugins.php`:

```php
'SpaceReservation' => [
    'namespace' => 'SpaceReservation',
    'config' => [
        'enabled' => true,
    ]
]
```

3. Execute as migrações de banco de dados acessando `/atualizar-repositorio` como administrador

## Configuração do Espaço

Para habilitar reservas em um espaço:

1. Edite o espaço
2. Na seção "Configurações de Reserva", marque "Permitir reservas neste espaço"
3. Configure:
   - **Instruções para reserva** - Informações específicas para solicitantes
   - **Capacidade máxima** - Limite de pessoas (0 = sem limite)
   - **Dias mínimos de antecedência** - Padrão: 2 dias
   - **Dias máximos de antecedência** - Padrão: 90 dias

## Fluxo de Uso

### Solicitante

1. Acesse a página de um espaço com reservas habilitadas
2. Clique na aba "Reservas"
3. Selecione uma data disponível no calendário
4. Preencha o formulário com:
   - Horário de início e término
   - Número de pessoas
   - Finalidade do uso
   - Requisitos especiais (opcional)
5. Aguarde aprovação do gestor

### Gestor do Espaço

1. Recebe notificação de nova solicitação
2. Acesse a lista de reservas do espaço
3. Visualize detalhes da solicitação
4. Aprove ou rejeite com justificativa

## API Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/space-reservation/availability` | Verifica disponibilidade |
| POST | `/api/space-reservation` | Criar reserva |
| GET | `/api/space-reservation` | Listar minhas reservas |
| GET | `/api/space-reservation/manage` | Listar reservas (gestor) |
| PATCH | `/api/space-reservation/:id/approve` | Aprovar reserva |
| PATCH | `/api/space-reservation/:id/reject` | Rejeitar reserva |
| PATCH | `/api/space-reservation/:id/cancel` | Cancelar reserva |

## Requisitos

- Mapa Cultural versão compatível com Doctrine ORM 2.x
- PHP 8.0+
- PostgreSQL

## Estrutura de Arquivos

```
SpaceReservation/
├── Plugin.php                 # Classe principal
├── Controller.php             # API endpoints
├── config.php                 # Configurações
├── db-updates.php             # Migrações
├── Entities/
│   └── SpaceReservation.php   # Entidade Doctrine
├── layouts/parts/space-reservation/
│   ├── tab.php               # Aba na página do espaço
│   ├── tab-content.php       # Conteúdo da aba
│   ├── calendar-component.php # Componente do calendário
│   └── space-config.php      # Configurações no formulário
├── assets/
│   ├── css/space-reservation.css
│   └── js/space-reservation.js
└── translations/
    └── pt_BR.po
```

## Licença

GPL-3.0
