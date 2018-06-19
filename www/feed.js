const API_ENDPOINT = '/index.php';


function createTaskEditElem(id, title) {
    let li = document.createElement('li');
    li.setAttribute(task_uid_attr, encodeURIComponent(id));
    let input = document.createElement('input');
    input.setAttribute('type', 'text')
    input.setAttribute('maxlength', LIMIT_TASK_LEN)
    input.setAttribute('value', title)
    input.appendChild(document.createTextNode(title))
    li.appendChild(input);
    return li;
}


function createTaskReadElem(el) {
    let li = document.createElement('li');
    li.setAttribute(task_uid_attr, encodeURIComponent(el.id));
    let checkbox = document.createElement('input');
    checkbox.setAttribute('id', el.id)
    checkbox.setAttribute('type', 'checkbox')
    if (el.checked && el.checked > 0) {
        checkbox.checked = true;
        if (!el.edit) {
            checkbox.setAttribute('disabled', 'disabled');
        }
    }
    let label = document.createElement('label')
    label.setAttribute('for', el.id)
    label.appendChild(document.createTextNode(el.title));
    li.appendChild(checkbox);
    li.appendChild(label);
    return li;
}


function loadTasks() {
    let current_user_id = document.querySelector('.js-select-role').value;


    fetch(new Request(`${API_ENDPOINT}?action=feed&user_id=${current_user_id}`), {credentials: 'same-origin',})
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


function handleChangeTaskTitle(e, list_uid) {
    // todo trottling
    let input = e.target
    let new_value = input.value;
    let current_li = input.closest(`li[${task_uid_attr}]`);
    let task_uid = current_li.getAttribute(task_uid_attr)

    // add task if value not empty
    if (!!new_value) {
        let form = new FormData()
        form.append('title', new_value);
        fetch(`/list/${list_uid}/task/${task_uid}/upsert`, {
            credentials: 'same-origin',
            method: "PUT",
            body: form
        })
            .then(function(response) { return response.json(); })
            .then(function(response_json) {
                current_li.setAttribute(task_uid_attr, encodeURIComponent(response_json['task_uid']))
            });
    }
}


function handleChangeTaskCheck(e, list_uid) {
    // todo trottling
    let input = e.target
    let new_value = input.checked;
    let current_li = input.closest(`li[${task_uid_attr}]`);
    let task_uid = current_li.getAttribute(task_uid_attr)

    let form = new FormData()
    form.append('state', Number(new_value));
    return fetch(`/list/${list_uid}/task/${task_uid}/state`, {
        credentials: 'same-origin',
        method: "PUT",
        body: form
    })
        .then(function(response) {
            if (response.status == 409) {
                loadTasks(list_uid, true);
            }
        });
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