<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Carteira Financeira</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @stack('styles')
</head>
<body>

    @yield('content')

    <div class="modal fade" id="reverseTransactionModal" tabindex="-1" aria-labelledby="reverseTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="reverseTransactionModalLabel">Confirmar Estorno de Transação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja estornar a transação de ID <strong id="modalTransactionId"></strong>?</p>
                    <p>Esta operação pode ser irreversível ou ter consequências financeiras.</p>
                    <div class="mb-3">
                        <label for="reverseReason" class="form-label">Motivo do Estorno (opcional):</label>
                        <input type="text" class="form-control" id="reverseReason">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmReverseButton">Confirmar Estorno</button>
                </div>
            </div>
        </div>
    </div>

    <div aria-live="polite" aria-atomic="true" class="position-relative">
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        // Função para mostrar toasts
        function showToast(message, type = 'success') {
            const toastContainer = $('.toast-container');
            const toastId = 'toast-' + Date.now();
            let toastClass = '';
            let headerBgClass = '';
            let headerTextClass = 'text-white';

            switch (type) {
                case 'success':
                    toastClass = 'bg-success';
                    headerBgClass = 'bg-success';
                    break;
                case 'error':
                    toastClass = 'bg-danger';
                    headerBgClass = 'bg-danger';
                    break;
                case 'warning':
                    toastClass = 'bg-warning';
                    headerBgClass = 'bg-warning';
                    headerTextClass = ''; // warning tem texto escuro por padrão
                    break;
                case 'info':
                    toastClass = 'bg-info';
                    headerBgClass = 'bg-info';
                    break;
                default:
                    toastClass = 'bg-secondary';
                    headerBgClass = 'bg-secondary';
            }

            const toastHtml = `
                <div class="toast align-items-center ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body ${headerTextClass}">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            toastContainer.append(toastHtml);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl);
            toast.show();

            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
