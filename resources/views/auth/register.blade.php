@extends('layouts.app')

@section('content')
<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="card-title text-center mb-4">Criar Nova Conta</h2>

            <form id="registerForm">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome:</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Senha:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmar Senha:</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Cadastrar</button>
            </form>

            <div class="text-center">
                Já tem uma conta? <a href="{{ route('login.show') }}">Faça login aqui</a>.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const formData = {
                name: $('#name').val(),
                email: $('#email').val(),
                password: $('#password').val(),
                password_confirmation: $('#password_confirmation').val()
            };

            $.ajax({
                url: '{{ route('api.register') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    showToast(response.message || 'Cadastro realizado com sucesso!', 'success');
                    setTimeout(function() {
                        window.location.href = '{{ route('login.show') }}';
                    }, 2000);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = 'Ocorreu um erro inesperado no registro.';
                    let displayType = 'error';

                    if (jqXHR.status === 422) { // Erro de validação
                        errorMessage = 'Por favor, corrija os erros do formulário:';
                        $.each(jqXHR.responseJSON.errors, function(key, value) {
                            $(`#${key}`).addClass('is-invalid');
                            $(`#${key}`).after(`<div class="invalid-feedback">${value[0]}</div>`);
                            errorMessage += `\n- ${value[0]}`; // Adiciona ao toast
                        });
                        displayType = 'warning'; // Avisa que há erros no formulário
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }

                    showToast(errorMessage, displayType);
                }
            });
        });
    });
</script>
@endpush
