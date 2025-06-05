@extends('layouts.app')

@section('content')
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('wallet.dashboard.show') }}">
            <img src="{{ asset('images/logos/logo.svg') }}" alt="Logo" style="height: 30px; width: auto;" class="me-2">
            Minha Carteira
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#dashboard">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#deposit">Depositar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#transfer">Transferir</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#transactions">Transações</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item d-flex align-items-center me-3">
                    <span class="navbar-text text-white">Olá, <strong id="userName">Usuário</strong>!</span>
                </li>
                <li class="nav-item">
                    <button class="btn btn-outline-light" id="logoutButton">Sair</button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Dashboard da Carteira</h1>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Saldo Atual</h5>
                    <p class="card-text fs-3">R$ <span id="currentBalance">0.00</span></p>
                    <button class="btn btn-info btn-sm" id="refreshBalanceButton">Atualizar Saldo</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Formulário de Depósito --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Depositar Dinheiro</h5>
                    <form id="depositForm">
                        <div class="mb-3">
                            <label for="depositAmount" class="form-label">Valor:</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="depositAmount" required>
                        </div>
                        <div class="mb-3">
                            <label for="depositDescription" class="form-label">Descrição (opcional):</label>
                            <input type="text" class="form-control" id="depositDescription">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Depositar</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Formulário de Transferência --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Transferir Dinheiro</h5>
                    <form id="transferForm">
                        <div class="mb-3">
                            <label for="recipientEmail" class="form-label">Email do Destinatário:</label>
                            <input type="email" class="form-control" id="recipientEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="transferAmount" class="form-label">Valor:</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="transferAmount" required>
                        </div>
                        <div class="mb-3">
                            <label for="transferDescription" class="form-label">Descrição (opcional):</label>
                            <input type="text" class="form-control" id="transferDescription">
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Transferir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Seção de Transações --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Histórico de Transações</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Descrição</th>
                                    <th>Envolvido</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                </tbody>
                        </table>
                    </div>
                    <nav>
                        <ul class="pagination justify-content-center" id="transactionsPagination">
                            </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Função auxiliar para obter o token JWT
    function getJwtToken() {
        return localStorage.getItem('jwt_token');
    }

    // Função para configurar cabeçalhos AJAX com JWT
    function setAuthHeader(xhr) {
        const token = getJwtToken();
        if (token) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + token);
        } else {
            showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
            setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
        }
    }

    // Função para carregar o nome do usuário logado
    function loadUserName() {
        $.ajax({
            url: '{{ route('api.me') }}',
            type: 'POST',
            beforeSend: setAuthHeader,
            success: function(response) {
                if (response.success && response.data && response.data.name) {
                    $('#userName').text(response.data.name);
                    localStorage.setItem('user_name', response.data.name);
                } else {
                    showToast('Não foi possível carregar o nome do usuário.', 'warning');
                    $('#userName').text('Usuário');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.status === 401) {
                    showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                    setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                } else {
                    showToast('Erro ao carregar dados do usuário: ' + (jqXHR.responseJSON.message || 'Erro desconhecido.'), 'error');
                }
                $('#userName').text('Usuário');
            }
        });
    }

    // Função para carregar saldo
    function loadBalance() {
        $.ajax({
            url: '{{ route('api.wallet.balance') }}',
            type: 'GET',
            beforeSend: setAuthHeader,
            success: function(response) {
                $('#currentBalance').text(response.data.balance);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.status === 401) {
                    showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                    setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                } else {
                    showToast('Erro ao carregar saldo: ' + (jqXHR.responseJSON.message || 'Erro desconhecido.'), 'error');
                }
            }
        });
    }

    // Função para carregar transações
    function loadTransactions(page = 1) {
        $.ajax({
            url: '{{ route('api.wallet.transactions') }}?page=' + page,
            type: 'GET',
            beforeSend: setAuthHeader,
            success: function(response) {
                const transactions = response.data.transactions;
                const pagination = response.data.pagination;
                const tableBody = $('#transactionsTableBody');
                tableBody.empty();

                if (transactions.length === 0) {
                    tableBody.append('<tr><td colspan="7" class="text-center">Nenhuma transação encontrada.</td></tr>'); // Colspan ajustado
                } else {
                    $.each(transactions, function(index, transaction) {
                        let involvedPartyText = '-';
                        if (transaction.type_key === 'deposit' || transaction.type_key === 'deposit_reversal') {
                            involvedPartyText = 'Você'; // Para depósitos, o envolvido é o próprio usuário
                        } else if (transaction.involved_party) {
                            involvedPartyText = transaction.involved_party;
                        }

                        const row = `
                            <tr>
                                <td>${transaction.id}</td>
                                <td>${transaction.type}</td>
                                <td>R$ ${transaction.amount}</td>
                                <td>${transaction.description || '-'}</td>
                                <td>${involvedPartyText}</td>
                                <td>${new Date(transaction.created_at).toLocaleString()}</td>
                                <td>
                                    ${!transaction.is_effectively_reversed && transaction.type_key !== 'transfer_received' ?
                                        `<button class="btn btn-sm btn-danger reverse-transaction" data-id="${transaction.id}">Estornar</button>`
                                        : '-'
                                    }
                                </td>
                            </tr>
                        `;
                        tableBody.append(row);
                    });
                }
                renderPagination(pagination);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 if (jqXHR.status === 401) {
                    showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                    setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                 } else {
                    showToast('Erro ao carregar transações: ' + (jqXHR.responseJSON.message || 'Erro desconhecido.'), 'error');
                 }
            }
        });
    }

    // Função para renderizar a paginação
    function renderPagination(pagination) {
        const paginationUl = $('#transactionsPagination');
        paginationUl.empty();

        if (pagination.last_page <= 1) { return; }

        const currentPage = pagination.current_page;
        const lastPage = pagination.last_page;

        paginationUl.append(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a>
            </li>
        `);

        for (let i = 1; i <= lastPage; i++) {
            paginationUl.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        paginationUl.append(`
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Próximo</a>
            </li>
        `);
    }

    // Document Ready - Inicialização
    $(document).ready(function() {
        loadUserName();
        loadBalance();
        loadTransactions();

        // Botão de Atualizar Saldo (mesmo código)
        $('#refreshBalanceButton').on('click', function() {
            loadBalance();
            showToast('Saldo atualizado!', 'info');
        });

        // Paginação click event (mesmo código)
        $(document).on('click', '#transactionsPagination .page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) { loadTransactions(page); }
        });

        // Formulário de Depósito (mesmo código)
        $('#depositForm').on('submit', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            const amount = $('#depositAmount').val();
            const description = $('#depositDescription').val();
            $.ajax({
                url: '{{ route('api.wallet.deposit') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ amount: parseFloat(amount), description: description }),
                beforeSend: setAuthHeader,
                success: function(response) {
                    showToast(response.message || 'Depósito realizado com sucesso!', 'success');
                    loadBalance();
                    loadTransactions();
                    $('#depositAmount').val('');
                    $('#depositDescription').val('');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Erro ao realizar depósito.';
                    let displayType = 'error';
                    if (jqXHR.status === 422) {
                        $.each(jqXHR.responseJSON.errors, function(key, value) {
                            $(`#deposit${key.charAt(0).toUpperCase() + key.slice(1)}`).addClass('is-invalid').after(`<div class="invalid-feedback">${value[0]}</div>`);
                            errorMessage = value[0];
                        });
                        displayType = 'warning';
                    } else if (jqXHR.status === 401) {
                         showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                         setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                         return;
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    showToast(errorMessage, displayType);
                }
            });
        });

        // Formulário de Transferência
        $('#transferForm').on('submit', function(e) {
            e.preventDefault();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            const recipientEmail = $('#recipientEmail').val();
            const amount = $('#transferAmount').val();
            const description = $('#transferDescription').val();
            $.ajax({
                url: '{{ route('api.wallet.transfer') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ recipient_email: recipientEmail, amount: parseFloat(amount), description: description }),
                beforeSend: setAuthHeader,
                success: function(response) {
                    showToast(response.message || 'Transferência realizada com sucesso!', 'success');
                    loadBalance();
                    loadTransactions();
                    $('#recipientEmail').val('');
                    $('#transferAmount').val('');
                    $('#transferDescription').val('');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Erro ao realizar transferência.';
                    let displayType = 'error';
                    if (jqXHR.status === 422) {
                        $.each(jqXHR.responseJSON.errors, function(key, value) {
                            let inputId = key.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                            if (key === 'recipient_email') inputId = 'recipientEmail';
                            $(`#${inputId}`).addClass('is-invalid').after(`<div class="invalid-feedback">${value[0]}</div>`);
                            errorMessage = value[0];
                        });
                        displayType = 'warning';
                    } else if (jqXHR.status === 400 || jqXHR.status === 404) {
                        errorMessage = jqXHR.responseJSON.message;
                        displayType = 'error';
                    } else if (jqXHR.status === 401) {
                         showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                         setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                         return;
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    showToast(errorMessage, displayType);
                }
            });
        });

        // Botão de Estornar Transação
        let transactionToReverseId = null;
        $(document).on('click', '.reverse-transaction', function() {
            transactionToReverseId = $(this).data('id');
            $('#modalTransactionId').text(transactionToReverseId);
            $('#reverseReason').val('');
            const reverseModal = new bootstrap.Modal(document.getElementById('reverseTransactionModal'));
            reverseModal.show();
        });

        $('#confirmReverseButton').on('click', function() {
            const reason = $('#reverseReason').val();
            const reverseModalInstance = bootstrap.Modal.getInstance(document.getElementById('reverseTransactionModal'));
            if (reverseModalInstance) { reverseModalInstance.hide(); }
            $.ajax({
                url: '{{ route('api.wallet.reverse') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ transaction_id: transactionToReverseId, reason: reason }),
                beforeSend: setAuthHeader,
                success: function(response) {
                    showToast(response.message || 'Transação estornada com sucesso!', 'success');
                    loadBalance();
                    loadTransactions();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Erro ao estornar transação.';
                    let displayType = 'error';
                    if (jqXHR.status === 422) {
                        errorMessage = 'Dados inválidos para estorno.';
                        $.each(jqXHR.responseJSON.errors, function(key, value) {
                            errorMessage += `\n- ${value[0]}`;
                        });
                        displayType = 'warning';
                    } else if (jqXHR.status === 400 || jqXHR.status === 404 || jqXHR.status === 500) {
                        errorMessage = jqXHR.responseJSON.message || errorMessage;
                        displayType = 'error';
                    } else if (jqXHR.status === 401) {
                         showToast('Sessão expirada. Por favor, faça login novamente.', 'error');
                         setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                         return;
                    }
                    showToast(errorMessage, displayType);
                }
            });
        });

        // Botão de Logout
        $('#logoutButton').on('click', function() {
            $.ajax({
                url: '{{ route('api.logout') }}',
                type: 'POST',
                beforeSend: setAuthHeader,
                success: function(response) {
                    showToast(response.message || 'Deslogado com sucesso!', 'success');
                    localStorage.removeItem('jwt_token');
                    localStorage.removeItem('expires_in');
                    localStorage.removeItem('user_name');
                    setTimeout(() => window.location.href = '{{ route('login.show') }}', 1000);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showToast(jqXHR.responseJSON.message || 'Erro ao deslogar. Tente novamente.', 'error');
                    localStorage.removeItem('jwt_token');
                    localStorage.removeItem('expires_in');
                    localStorage.removeItem('user_name');
                    setTimeout(() => window.location.href = '{{ route('login.show') }}', 1500);
                }
            });
        });
    });
</script>
@endpush
