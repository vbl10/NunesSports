const tableProdutos = document.getElementById("tableProdutos");
const inputCodigo = document.getElementById("inputCodigo");
const inputNome = document.getElementById("inputNome");
const inputDescricao = document.getElementById("inputDescricao");
const inputPreco = document.getElementById("inputPreco");
const pErroDeEntrada = document.getElementById("pErroDeEntrada");

const productsApiBaseUrl = "http://localhost:80/NunesSports/backend/products.php";

let produtos = [];

function atualizarProdutos() {

    fetch(productsApiBaseUrl)
    .then(resp => resp.json())
    .then(result => {
        produtos = result.rows;
        tableProdutos.innerHTML = result.rows.map((produto, idx) => `
            <tr onclick="aoClicarProduto(${idx})">
                <td>${produto.codigo}</td>
                <td>${produto.nome}</td>
                <td>${produto.preco}</td>
                <td>${produto.descricao}</td>
            </tr>
        `).join('');
    })
}

function aoClicarProduto(linha) {
    inputCodigo.value = produtos[linha].codigo;
    inputNome.value = produtos[linha].nome;
    inputDescricao.value = produtos[linha].descricao;
    inputPreco.value = produtos[linha].preco;
}

function inserirProduto() {
    const nome = inputNome.value;
    const descricao = inputDescricao.value;
    const preco = inputPreco.value;
    fetch(productsApiBaseUrl, {
        method: "POST",
        body: JSON.stringify({nome, descricao, preco})
    })
    .then(() => atualizarProdutos());
}

function removerProduto() {
    const codigo = inputCodigo.value;
    fetch(productsApiBaseUrl, {
        method: "DELETE",
        body: JSON.stringify({codigo})
    })
    .then(() => atualizarProdutos());
}

function atualizarProduto() {
    const codigo = inputCodigo.value;
    const nome = inputNome.value;
    const descricao = inputDescricao.value;
    const preco = inputPreco.value;

    let req = {};
    if (codigo) {
        req['codigo'] = codigo;
    }
    else {
        console.error("Deve ser passado um código para atualizar um produto");
        return;
    }

    if (nome) 
        req['nome'] = nome;
    if (descricao) 
        req['descricao'] = descricao;
    if (preco) 
        req['preco'] = preco;

    fetch(productsApiBaseUrl, {
        method: "PATCH",
        body: JSON.stringify(req)
    })
    .then(() => atualizarProdutos());
}

function main() {
    atualizarProdutos();
}

main();