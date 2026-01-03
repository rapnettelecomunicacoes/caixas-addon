// ============================================================================
// FUNÇÃO AUXILIAR: Detecta automaticamente o caminho base do addon
// Funciona independente do nome da pasta onde o addon está instalado
// ============================================================================
function getAddonBasePath() {
  var currentPath = window.location.pathname;
  var match = currentPath.match(/\/admin\/addons\/[^\/]+\//);
  return match ? match[0] : '/admin/addons/mapa8/';
}

//===========================BUSCAR DADOS DA OLT E MONTA A URL BASE PARA ONU==================================
function consultarOnusNao() {
  var serial = document.getElementById("serial").value;
  var nome_olt = $("#OLT").val();
  console.log("OLT Selecionada:", nome_olt);
  var urlBase_olt =
    window.location.origin +
    getAddonBasePath() + "src/cto/componente/olt/buscar_dados_olt.php";

  // Verifica se o checkbox está marcado e captura o idCliente (se necessário)
  var idCliente = document.getElementById("ativoVisualizar").checked
    ? document.getElementById("idCliente").value
    : null;
  console.log(
    `Chamando buscarOnusNao com Serial: ${serial || "N/A"}, idCliente: ${
      idCliente || "N/A"
    }`
  );

  $.ajax({
    url: urlBase_olt,
    type: "POST",
    dataType: "json",
    data: { nome_olt: nome_olt },
    success: function (response) {
      if (!response.success) {
        alert(response.output);
        return;
      }

      var endereco_ip = response.data.endereco_ip;
      var porta_ssh = response.data.portaSSH;
      var usuario = response.data.usuario;
      var senha = response.data.senha;
      var fabricante_olt = response.data.fabricante;
      var modelo_olt = response.data.modelo;

      //define a urlBase de ONU
      var urlBase_onu =
        window.location.origin +
        getAddonBasePath() + "src/cto/componente/olt/homologadas/" + fabricante_olt + "/" + modelo_olt;
      console.log("Conectando à OLT:", endereco_ip, "Porta:", porta_ssh);
      // Passa serial, idCliente e as variáveis de conexão para a função buscarOnusNao
      buscarOnusNao(
        serial,
        urlBase_onu,
        idCliente,
        endereco_ip,
        porta_ssh,
        usuario,
        senha
      );
    },
    error: function (xhr) {
      console.error("Erro na requisição AJAX (buscar OLT):", xhr.responseText);
      alert("Erro ao buscar dados da OLT.");
    },
  });
}

//===========================BUSCAR ONU NAO AUTORIZADAS===================================
function buscarOnusNao(
  serial,
  urlBase_onu,
  idCliente,
  endereco_ip,
  porta_ssh,
  usuario,
  senha
) {
  mostrarLoading();
  var urlConsultarOnusNao = urlBase_onu + "/consultar_onus_nao.php";
  $.ajax({
    url: urlConsultarOnusNao,
    type: "POST",
    dataType: "json",
    data: {
      host: endereco_ip,
      port: porta_ssh,
      username: usuario,
      password: senha,
      sn: serial,
      idCliente: idCliente, // Passa idCliente se necessário
    },
    success: function (response) {
      if (!response.success) {
        alert(response.output);
        esconderLoading();
        return;
      }

      if (response.success) {
        if (Array.isArray(response.onus) && response.onus.length > 0) {
          esconderLoading();
          // Passa todos os parâmetros necessários para preencherTabelaOnusNao
          preencherTabelaOnusNao(
            response.onus,
            urlBase_onu,
            idCliente,
            endereco_ip,
            porta_ssh,
            usuario,
            senha
          );
          $("#janelaOnusNao").show();
        } else {
          alert(response.output);
          esconderLoading();
        }
      } else {
        alert(response.output);
        esconderLoading();
      }
    },
    error: function (xhr) {
      alert("Modelo de OLT ainda não homologado.");
      esconderLoading();
    },
  });
}

//===========================BUSCAR DADOS DA OLT E MONTA A URL BASE PARA ONU====================================
function consultarOnusAll() {
  var serial = document.getElementById("serial").value;
  var nome_olt = $("#OLT").val();
  console.log("OLT Selecionada:", nome_olt);
  var urlBase_olt =
    window.location.origin +
    getAddonBasePath() + "src/cto/componente/olt/buscar_dados_olt.php";

  $.ajax({
    url: urlBase_olt,
    type: "POST",
    dataType: "json",
    data: { nome_olt: nome_olt },
    success: function (response) {
      if (!response.success) {
        alert(response.output);
        esconderLoading();
        return;
      }

      endereco_ip = response.data.endereco_ip;
      porta_ssh = response.data.portaSSH;
      usuario = response.data.usuario;
      senha = response.data.senha;
      fabricante_olt = response.data.fabricante;
      modelo_olt = response.data.modelo;

      var urlBase_onu =
        window.location.origin +
        `" + getAddonBasePath() + "src/cto/componente/olt/homologadas/${fabricante_olt}/${modelo_olt}`;
      console.log("Conectando à OLT:", endereco_ip, "Porta:", porta_ssh);

      console.log(
        serial
          ? `Chamando buscarOnusAll com Serial: ${serial}`
          : "Chamando buscarOnusAll - Sem serial informado"
      );
      buscarOnusAll(serial, urlBase_onu);
    },
    error: function (xhr) {
      alert("Erro ao buscar dados da OLT.");
    },
  });
}

function buscarOnusAll(serial, urlBase_onu) {
  var urlConsultarOnusAll = urlBase_onu + "/consultar_onus_all.php";
  console.log("Chamando:", urlConsultarOnusAll);
  console.log(
    "Conectando à OLT:",
    endereco_ip,
    "Porta:",
    porta_ssh,
    "Usuário:",
    usuario,
    "Senha:",
    senha,
    "Serial:",
    serial
  );
  mostrarLoading();

  $.ajax({
    url: urlConsultarOnusAll,
    type: "POST",
    dataType: "json",
    data: {
      host: endereco_ip,
      port: porta_ssh,
      username: usuario,
      password: senha,
      sn: serial,
    },
    success: function (response) {
      console.log("Resposta do servidor:", response);
      if (response.success) {
        if (Array.isArray(response.onus) && response.onus.length > 0) {
          esconderLoading();
          preencherTabelaOnusAll(response.onus, urlBase_onu);
          $("#janelaOnusAll").show();
        } else {
          alert(response.output);
          esconderLoading();
        }
      } else {
        alert(response.output);
        esconderLoading();
      }
    },
    error: function (xhr) {
      console.log("Erro na requisição AJAX:", xhr);
      console.log("Status:", xhr.status);
      console.log("Resposta:", xhr.responseText);
      console.log("Pronto:", xhr.readyState);
      console.log("Erro:", xhr.statusText);

      if (xhr.status === 404) {
        alert("Erro: O modelo de OLT ainda não foi homologado.");
      } else if (xhr.status === 500) {
        alert("Erro interno no servidor. Verifique a configuração da OLT.");
      } else {
        alert("Erro ao conectar-se à OLT. Verifique os dados informados.");
      }

      esconderLoading();
    },
  });
}

function preencherTabelaOnusNao(
  onus,
  urlBase_onu,
  idCliente,
  endereco_ip,
  porta_ssh,
  usuario,
  senha
) {
  console.log("Dados recebidos para preencher a tabela:", onus); // Debug
  console.log("idCliente recebido:", idCliente); // Verificando se o idCliente está sendo passado corretamente

  var tabelaOnusNao = document
    .getElementById("tabelaOnusNao")
    .getElementsByTagName("tbody")[0];
  tabelaOnusNao.innerHTML = ""; // Limpa a tabela antes de preencher

  onus.forEach(function (onu) {
    var novaLinha = tabelaOnusNao.insertRow();
    console.log("idCliente ao definir o botão:", idCliente); // Verifique o valor de idCliente

    novaLinha.innerHTML = `
            <td>${onu.olt_index}</td>
            <td>${onu.model}</td>
            <td>${onu.sn}</td>
            <td>${onu.pw}</td>
            <td>
                <button class="autorizarBtn" data-sn="${onu.sn}" data-modelo="${onu.model}" data-interface="${onu.olt_index}" data-urlBase_onu="${urlBase_onu}" data-idcliente="${idCliente}" data-endereco_ip="${endereco_ip}" data-porta_ssh="${porta_ssh}" data-usuario="${usuario}" data-senha="${senha}">Autorizar</button>
            </td>
        `;

    // Não é necessário redefinir idCliente dentro do click
    novaLinha
      .querySelector(".autorizarBtn")
      .addEventListener("click", function () {
        var sn = this.getAttribute("data-sn");
        var fsp = this.getAttribute("data-interface");
        var modelo = this.getAttribute("data-modelo");
        var interface = this.getAttribute("data-interface");
        var idCliente = this.getAttribute("data-idcliente"); // O valor correto do idCliente
        var endereco_ip = this.getAttribute("data-endereco_ip");
        var porta_ssh = this.getAttribute("data-porta_ssh");
        var usuario = this.getAttribute("data-usuario");
        var senha = this.getAttribute("data-senha");

        console.log(`Chamada para autorizar ONU com o idCliente: ${idCliente}`); // Debug

        autorizarOnu(
          sn,
          fsp,
          modelo,
          interface,
          urlBase_onu,
          idCliente,
          endereco_ip,
          porta_ssh,
          usuario,
          senha
        );
      });
  });
}

//===========================MONTAR TABELA COM ONU AUTORIZADA===================================
let currentPage = 1;
const rowsPerPage = 6;
let onusData = [];
let urlBaseOnuGlobal = "";

function preencherTabelaOnusAll(onus, urlBase_onu, page = 1) {
  onusData = onus;
  urlBaseOnuGlobal = urlBase_onu;

  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const onusPaginated = onus.slice(start, end);

  var tabelaOnusNao = document
    .getElementById("tabelaOnusAll")
    .getElementsByTagName("tbody")[0];
  tabelaOnusNao.innerHTML = "";

  onusPaginated.forEach(function (onu) {

    var novaLinha = tabelaOnusNao.insertRow();

    const [rxPercent, rxStatus] = getStatus(onu?.rx ?? -50);
    const [txPercent, txStatus] = getStatus(onu?.tx ?? -50);

    novaLinha.innerHTML = `
          <td>${onu?.interface}</td>
          <td>${onu?.onu_index}</td>
          <td>${onu?.model}</td>
          <td>${onu?.sn}</td>
          <td>
              <div class="signal-bar" id="tx-signal-${onu?.onu_index}">
                  <div class="bar-1"></div>
                  <div class="bar-2"></div>
                  <div class="bar-3"></div>
                  <div class="bar-4"></div>
                  <div class="bar-5"></div>
              </div>
              <p id="tx-text-${onu?.onu_index}" class="mt-2" style="font-size: 10px;">${txStatus}</p>
          </td>
          <td>
              <div class="signal-bar" id="rx-signal-${onu.onu_index}">
                  <div class="bar-1"></div>
                  <div class="bar-2"></div>
                  <div class="bar-3"></div>
                  <div class="bar-4"></div>
                  <div class="bar-5"></div>
              </div>
              <p id="rx-text-${onu?.onu_index}" class="mt-2" style="font-size: 10px;">${rxStatus}</p>
          </td>
          <td>
              <button class="desautorizarBtn" data-indice="${onu?.onu_index}" data-modelo="${onu?.model}" data-sn="${onu?.sn}" data-interface="${onu?.interface}" data-urlBase_onu="${urlBase_onu}">Desautorizar</button>
          </td>
      `;

    updateSignalBars(
      document.querySelectorAll(`#rx-signal-${onu?.onu_index} div`),
      rxPercent
    );
    updateSignalBars(
      document.querySelectorAll(`#tx-signal-${onu?.onu_index} div`),
      txPercent
    );

    var botaoDesautorizar = novaLinha.querySelector(".desautorizarBtn");
    if (botaoDesautorizar) {
      botaoDesautorizar.addEventListener("click", function () {
        var indice = this.getAttribute("data-indice");
        var modeloOnu = this.getAttribute("data-modelo");
        var sn = this.getAttribute("data-sn");
        var interfaceOnu = this.getAttribute("data-interface");

        desautorizarOnu(indice, modeloOnu, sn, interfaceOnu, urlBase_onu);
      });
    } else {
      console.error(
        "Botão de desautorização não encontrado na linha:",
        novaLinha
      );
    }
  });

  criarPaginacao(onus.length, page);
}

function criarPaginacao(totalItems, page) {
  const totalPages = Math.ceil(totalItems / rowsPerPage);
  const paginacaoContainer = document.getElementById("paginacao");
  paginacaoContainer.innerHTML = "";

  // Botão "Anterior"
  const prevButton = document.createElement("button");
  prevButton.innerText = "Anterior";
  prevButton.classList.add("paginacao-btn");
  prevButton.disabled = page === 1; // Desativa se estiver na primeira página

  prevButton.addEventListener("click", function () {
    if (page > 1) {
      currentPage = page - 1;
      preencherTabelaOnusAll(onusData, urlBaseOnuGlobal, currentPage);
    }
  });

  paginacaoContainer.appendChild(prevButton);

  // Texto "Página X de Y"
  const pageInfo = document.createElement("span");
  pageInfo.innerText = `Página ${page} de ${totalPages}`;
  pageInfo.classList.add("paginacao-info");
  paginacaoContainer.appendChild(pageInfo);

  // Botão "Próximo"
  const nextButton = document.createElement("button");
  nextButton.innerText = "Próximo";
  nextButton.classList.add("paginacao-btn");
  nextButton.disabled = page === totalPages;

  nextButton.addEventListener("click", function () {
    if (page < totalPages) {
      currentPage = page + 1;
      preencherTabelaOnusAll(onusData, urlBaseOnuGlobal, currentPage);
    }
  });

  paginacaoContainer.appendChild(nextButton);
}

// Função para atualizar as barras de sinal
function updateSignalBars(bars, percent) {
  const percentValue = parseInt(percent);

  bars.forEach((bar, index) => {
    const barLimit = (index + 1) * 20; // Limites: 20%, 40%, 60%, 80%, 100%

    if (percentValue >= barLimit) {
      bar.classList.add("active");
    } else {
      bar.classList.remove("active");
    }
  });
}

// Função para determinar o status e porcentagem com base no valor de dBm
function getStatus(dBm) {
  const porcentagem = calcularPorcentagemSinal(dBm);
  let status = "";

  switch (porcentagem) {
    case 100:
      status = "Muito Forte";
      break;
    case 80:
      status = "Bom";
      break;
    case 60:
      status = "Aceitável";
      break;
    case 40:
      status = "Ruim";
      break;
    case 20:
      status = "Muito Ruim";
      break;
    default:
      status = "Offline";
      break;
  }

  return [porcentagem + "%", status];
}

function calcularPorcentagemSinal(dBm) {
  if (dBm >= -12) {
    return 100; // Muito Forte
  } else if (dBm >= -22) {
    return 80; // Bom
  } else if (dBm >= -24) {
    return 60; // Aceitável
  } else if (dBm >= -26) {
    return 40; // Ruim
  } else if (dBm >= -35) {
    return 20; // Muito Ruim
  } else {
    return 0; // Sem Sinal
  }
}

//===========================AUTORIZAR ONU===================================
function autorizarOnu(
  sn,
  fsp,
  modelo,
  interface,
  urlBase_onu,
  idCliente,
  endereco_ip,
  porta_ssh,
  usuario,
  senha
) {
  mostrarLoading();
  var urlAutorizar = urlBase_onu + "/autorizar_onu.php";
  console.log(
    `Autorizando ONU com SN: ${sn}, FSP: ${fsp}, Modelo: ${modelo}, Interface: ${interface}, idCliente: ${idCliente}`
  );

  $.ajax({
    url: urlAutorizar,
    type: "POST",
    dataType: "json",
    data: {
      sn: sn,
      fsp: fsp,
      modelo: modelo,
      interface: interface,
      host: endereco_ip,
      port: porta_ssh,
      username: usuario,
      password: senha,
      idCliente: idCliente, // Inclui idCliente na requisição
    },
    success: function (response) {
      if (response.success) {
        alert("ONU autorizada com sucesso!");
        $("#fecharJanelaOnusNao").click();
        esconderLoading();
        location.reload();
      } else {
        alert("Erro ao autorizar ONU: " + response.output);
        esconderLoading();
      }
    },
    error: function (xhr) {
      console.error("Erro na requisição (autorizar ONU):", xhr.responseText);
      alert("Falha na comunicação com o servidor.");
      esconderLoading();
    },
  });
}

$(document).ready(function () {
  $("#consultarOnusAll").on("click", function () {
    consultarOnusAll();
  });

  $("#fecharJanelaAll").on("click", function () {
    $("#janelaOnusAll").hide();
  });
});

//===========================DESAUTORIZAR ONU===================================
function desautorizarOnu(indice, modelo, sn, interface, urlBase_onu) {
  mostrarLoading();
  if (
    confirm(
      `Tem certeza de que deseja desautorizar a ONU ${sn} (ID ${indice})?`
    )
  ) {
    var urlDesautorizar = urlBase_onu + "/desautorizar_onu.php";
    // console.log(`Autorizando ONU: Modelo: ${modelo} | SN: ${sn} | Interface: ${interface} urlBaseOnu: ${urlBase_onu}`);
    $.ajax({
      url: urlDesautorizar,
      type: "POST",
      dataType: "json",
      data: {
        sn: sn,
        indice: indice,
        modelo: modelo,
        interface: interface,
        host: endereco_ip,
        port: porta_ssh,
        username: usuario,
        password: senha,
      },
      success: function (response) {
        if (response.success) {
          alert("ONU desautorizada com sucesso!");
          // console.log("ONU desautorizada com sucesso!");

          // Após desautorizar, fechar a modal de ONUs Autorizadas
          var btnFecharOnusAll = $("#fecharJanelaOnusAll");
          btnFecharOnusAll.click(); // Isso simula o clique no botão de fechar a modal
          esconderLoading();
          location.reload();
        } else {
          alert("Erro ao desautorizar ONU: " + response.output);
          // console.log("Erro ao desautorizar ONU: " + response.output);
          esconderLoading();
        }
      },
      error: function (xhr) {
        // console.error("Erro na requisição (desautorizar ONU):", xhr.responseText);
        alert("Falha na comunicação com o servidor.");
        esconderLoading();
      },
    });
  }
}

// Função para abrir a janela
function abrirJanela(idJanela) {
  document.getElementById(idJanela).style.display = "block"; // Exibir
}

// Função para fechar a janela
function fecharJanela(idJanela) {
  document.getElementById(idJanela).style.display = "none"; // Ocultar
}

$(document).ready(function () {
  // Fechar as modais
  $("#fecharJanelaOnusAll").on("click", function () {
    $("#janelaOnusAll").hide();
  });

  $("#fecharJanelaOnusNao, #fecharJanelaNao").on("click", function () {
    $("#janelaOnusNao").hide();
  });

  // Eventos de clique para autorizar e desautorizar ONUs
  $("#autorizarBtn").on("click", autorizarOnu);
  $("#desautorizarBtn").on("click", desautorizarOnu);

  // Evento de clique para consultar ONUs não autorizadas
  $("#consultarOnusNao").on("click", consultarOnusNao);
});

// Função para mostrar o loading
function mostrarLoading() {
  var loading = document.getElementById("loading");
  loading.style.display = "flex"; // Exibe o loading
}

// Função para esconder o loading
function esconderLoading() {
  var loading = document.getElementById("loading");
  loading.style.display = "none"; // Oculta o loading
}
