import axios from 'axios'

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    }
})

http.interceptors.response.use(
    response => response,
    error => {
        if (! error.response) {
            alert(window.translations.unableToConnect)

            return Promise.reject(error)
        }

        const status = error.response.status

        switch(status) {
            case 401:
                window.location.href = '/login'
                break

            case 419:
                window.location.reload()
                break

            case 403:
            case 404:
            case 500:
                alert(getErrorMessage(status));
                break;
        }

        return Promise.reject(error)
    }
);

function getErrorMessage(status) {
    const messages = {
        403: window.translations.permissionDenied,
        404: window.translations.resourceNotFound,
        500: window.translations.serverError,
    };

    return messages[status] || window.translations.unexpectedError;
}

export default http;
