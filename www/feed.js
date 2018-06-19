'use strict';

const API_ENDPOINT = '/api.php';


function getCurrentUser() {
    return document.querySelector('.js-select-role').value;
}


function initOrdersFeed(e) {
    if (!!e) {
        e.preventDefault();
    }

    let current_user_id = getCurrentUser();

    fetch(`${API_ENDPOINT}?action=feed&user_id=${current_user_id}`, {
        credentials: 'same-origin'
    })
        .then(function(response) { return response.json(); })
        .then(function(response_json) {
            let balanceContainer = document.querySelector('.js-user-balance');
            balanceContainer.innerHTML = '';
            balanceContainer.appendChild(document.createTextNode(response_json['data']['balance']));

            let container = document.querySelector('.js-feed');
            container.innerHTML = '';
            let orders = response_json['data']['orders'];
            orders.forEach(function(el, index, array) {
                let item = document.querySelector('.js-item-template').cloneNode(true);
                item.classList.remove("js-item-template");
                item.classList.remove("hide");
                item.classList.add("js-item");
                item.setAttribute('data-js-order-id', encodeURIComponent(el.id));
                item.querySelector('.js-item-title').appendChild(document.createTextNode(el.name));
                item.querySelector('.js-item-price').appendChild(document.createTextNode(el.price));
                container.appendChild(item);
            });
        })
}


function postNewOrder(e) {
    e.preventDefault();

    let current_user_id = getCurrentUser();
    let order_name = document.querySelector('.js-post-order-form input[name=name]').value;
    let order_price = document.querySelector('.js-post-order-form input[name=price]').value;

    let form = new FormData();
    form.append('name', order_name);
    form.append('price', order_price);
    fetch(`${API_ENDPOINT}?action=place_order&user_id=${current_user_id}`, {
        credentials: 'same-origin',
        method: "POST",
        body: form
    })
        .then(async function(response) {
            if (response.status != 201) {
                let errorContainer = document.querySelector('.js-post-order-error');
                errorContainer.innerHTML = '';
                let errors = (await response.json())['data'];
                console.log(errors);
                if (!!errors) {
                    errorContainer.appendChild(document.createTextNode(errors[0]))
                } else {
                    errorContainer.appendChild(document.createTextNode(`unknown error ${response.status}`))
                }
            } else {
                document.querySelector('.js-post-order-form input[name=name]').value = '';
                document.querySelector('.js-post-order-form input[name=price]').value = '';
                initOrdersFeed();
            }
        });

}


document.addEventListener('DOMContentLoaded', function() {
    initOrdersFeed();

    document.querySelector('.js-select-role').addEventListener('change', initOrdersFeed);

    document.querySelector('.js-post-order-form').addEventListener('submit', postNewOrder);

    // document.addEventListener('input',  function(e) {
    //     if (e.target.matches(`li[${task_uid_attr}] input`)) {
    //         handleChangeTaskTitle(e, list_uid_edit.getAttribute(list_uid_attr));
    //     }
    // });

});