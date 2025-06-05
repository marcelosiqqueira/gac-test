@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <div class="header-image-container text-center mb-4">
        <img src="{{ asset('images/cabecalho.jpg') }}" alt="Cabeçalho da Página" class="img-fluid w-100" style="max-height: 20rem; object-fit: cover;">
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="{{ asset('images/logos/logo.svg') }}" alt="Logo da Empresa" class="img-fluid" style="max-width: 150px;">
                        </div>
                        <h2 class="card-title text-center mb-4">Acessar Conta</h2>

                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Senha:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                        </form>

                        <div class="text-center">
                            Não tem uma conta? <a href="{{ route('register.show') }}">Crie uma aqui</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const formData = {
                email: $('#email').val(),
                password: $('#password').val()
            };

            $.ajax({
                url: '{{ route('api.login') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    localStorage.setItem('jwt_token', response.data.token);
                    localStorage.setItem('expires_in', response.data.expires_in);
                    showToast(response.message || 'Login realizado com sucesso!', 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route('wallet.dashboard.show') }}';
                    }, 1500);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Ocorreu um erro inesperado no login.';
                    let displayType = 'error';

                    if (jqXHR.status === 422) {
                        errorMessage = 'Por favor, preencha os campos obrigatórios.';
                        $.each(jqXHR.responseJSON.errors, function(key, value) {
                            $(`#${key}`).addClass('is-invalid');
                            $(`#${key}`).after(`<div class="invalid-feedback">${value[0]}</div>`);
                            errorMessage = value[0];
                        });
                        displayType = 'warning';
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                        if (jqXHR.status === 401) {
                            displayType = 'error';
                        }
                    }

                    showToast(errorMessage, displayType);
                }
            });
        });
    });
</script>
@endpush
