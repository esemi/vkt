const API_ENDPOINT = '/api.php';


function loadTasks() {
    let current_user_id = document.querySelector('.js-select-role').value;

    fetch(new Request(`${API_ENDPOINT}?action=feed&user_id=${current_user_id}`), {credentials: 'same-origin'})
        .then(function(response) { return response.json(); })
        .then(function(response_json) {
            let container = document.querySelector('.js-feed');
            container.innerHTML = '';
            let orders = response_json['orders'];
            orders.forEach(function(el, index, array) {
                console.log(el);
                let item = document.querySelector('js-item-template').cloneNode(true);
                item.classList.remove("js-item-template").add("js-item");
                item.setAttribute('data-js-order-id', encodeURIComponent(el.id));
                item.querySelector('.js-item-title').appendChild(document.createTextNode(el.name));
                item.querySelector('.js-item-price').appendChild(document.createTextNode(el.price));
                container.appendChild(item);
            });
        })
}

document.addEventListener('DOMContentLoaded', function() {
    loadTasks();

    document.querySelector('.js-select-role').addEventListener('change', loadTasks);

    // document.addEventListener('input',  function(e) {
    //     if (e.target.matches(`li[${task_uid_attr}] input`)) {
    //         handleChangeTaskTitle(e, list_uid_edit.getAttribute(list_uid_attr));
    //     }
    // });

});