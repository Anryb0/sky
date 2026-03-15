import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx';
import { useRoot } from "../context/RootContext.jsx";
import './Start.css';

function Start() {
  const { user, openModal } = useRoot();
  const navigate = useNavigate();
  const [loading,setLoading] = useState(true);
  const [loadingHosts,setLoadingHosts] = useState(true);
  const [hosts,setHosts] = useState(null);
  const [selectedHost,setSelectedHost] = useState(null);
  const [osData,setOsData] = useState(null);
  const	[resources, setResources] = useState(null);
  const [resourcesQ, setResourcesQ] = useState(null);
  const [selectedOs, setSelectedOs] = useState(null);
  const [selectedQ, setSelectedQ] = useState(1);
  const [totalPrice, setTotalPrice] = useState(0);
  const [servers, setServers] = useState(null);
  const [noHostsState, setNoHostsState] = useState(false);
  useEffect(() => {
	let xhr = new XMLHttpRequest();
	xhr.withCredentials = true;
    xhr.open('GET','https://anryb0.ru/sky/api/loadplans.php');
    xhr.send();
    xhr.onload = function(){
      if(xhr.status == 200){
        let response = JSON.parse(xhr.responseText);
        if(response.success){
          setLoading(false);
          setResources(response.resources);
		  var qarray = [];
		  var tmp = 0;
		  response.resources.forEach((item) => {
			  var qres = new Object();
			  qres.resource_id = item.resource_id;
			  qres.name = item.name;
			  qres.q = item.min_value;
			  qres.price = item.price;
			  tmp += item.min_value * item.price;
			  qarray.push(qres);
		  })
		  setTotalPrice(tmp)
		  setResourcesQ(qarray);
          setOsData(response.os);
          setSelectedOs(response.os[0].os_id);
        } 
        else{
          openModal(response, true);
        }
      }
      else{
        openModal('Ошибка ' + xhr.status, true);
      }
    }
	LoadHosts();
	
	},[user, loading]);
  function LoadHosts(){
	 setLoadingHosts(true)
	let xhr2 = new XMLHttpRequest();
    xhr2.open('GET','https://anryb0.ru/sky/api/checkserveravail.php');
	xhr2.withCredentials = true;
	xhr2.send();
    xhr2.onload = function(){
		if(xhr2.status == 200){
			let response = JSON.parse(xhr2.responseText);
			if(response.success){
				setHosts(response.hosts_avail);
				var noHosts = true;
				response.hosts_avail.forEach((item)=>{
					if(item.avail == true){
						noHosts = false
						setSelectedHost(item.host_id)
					}
				})
				setNoHostsState(noHosts);
				setLoadingHosts(false);
			}
			else{
				openModal(response, true);
			}
		}
		else{
			openModal('Ошибка ' + xhr2.status, true);
		}
	}
  }
  function Continue(){
    if(selectedQ < 1 || selectedQ > 20){
      openModal('Введите корректное количество от 1 до 20', true);
    }
	if(selectedHost == null){
	  openModal('Выберите сервер', true);
	}
    else{
      let xhr = new XMLHttpRequest;
      let formData = new FormData();
	  formData.append('host',selectedHost);
      formData.append('resources',JSON.stringify(resourcesQ));
      formData.append('q',selectedQ);
      formData.append('os_id',selectedOs);
      xhr.withCredentials = true;
      xhr.open('POST','https://anryb0.ru/sky/api/start.php');
      xhr.send(formData);
      xhr.onload = function(){
        if(xhr.status == 200){
          let response = JSON.parse(xhr.responseText);
          if(response.success){
            window.location.href = response.url;
          }
          else{
            openModal(response.message, true)
          }
        }
        else{
          openModal('Ошибка ' + xhr.status, true);
        }
      }
    }
  }
  const ChangeResourcesQ = (event, name) => {
	
	 const newValue = parseInt(event.target.value);
	  setResourcesQ(prevResourcesQ => 
		prevResourcesQ.map(item => 
		  item.name === name ? { ...item, q: newValue} : item
		)
	  );
	  var tmp = (
		resourcesQ.map(item => 
		  item.name === name ? { ...item, q: newValue} : item
		));
	  var pr = 0
	  tmp.forEach(item=>{
		  pr += item.price * item.q;
		  console.log(item, pr)
	  })
	  setTotalPrice(pr * selectedQ);
  }
  const ChangeOs = (id) => {
    setSelectedOs(id);
  }
  const onChangeQ = (event) => {
	setTotalPrice(totalPrice/selectedQ*event.target.value);
    setSelectedQ(event.target.value);
  } 
  const handleHostClick = (id) => {
	  setSelectedHost(id);
	  console.log(selectedHost)
  }
return (
  <>
    <Header />
    <main>
      <div>
        <h2>Настройте свой VDS</h2>
        <hr />
        {loading ? (
          <div className='spinner'></div>
        ) : (
          <>
            <h3><b>1 - </b>Укажите кол-во ресурсов:</h3>
            <div id='resgrid'>
              {resources.map((item, index) => {
                return (
                  <div className='glassy' key={index}>
                    <h3><b>{item.name}</b></h3>
                    <hr />
                    <div className='qc'>
                      <span>{item.min_value}</span>
                      <input 
                        type="range" 
                        onInput={(e) => ChangeResourcesQ(e, item.name)} 
                        min={item.min_value} 
                        max={item.max_value} 
                        step="1" 
                        value={resourcesQ[index].q} 
                      />
                      <span>{item.max_value}</span>
                    </div>
                    <p className='small'>
                      Итого: {resourcesQ[index].q} * {resourcesQ[index].price} RUB за шт. = {resourcesQ[index].price * resourcesQ[index].q} RUB
                    </p>
                  </div>
                );
              })}
            </div>
            <h3>Итого в месяц: <b>{totalPrice/selectedQ} RUB</b></h3>
            <hr />
            <h3><b>2 - </b>Выберите ОС для сервера:</h3>
            {osData.map((item) => {
              return (
                <div 
                  className={item.os_id == selectedOs ? 'glassy ilist selected' : 'glassy ilist'} 
                  onClick={(e) => ChangeOs(e, item.os_id)}
                  key={item.os_id}
                >
                  <b>{item.name}</b>
                </div>
              );
            })}
            <br />
            <hr />
            <h3>
              <b>3 - </b>Выберите срок (в мес.): 
              <span className='qc' id='qm'>
                <span>1 </span>
                <input 
                  type="range" 
                  onInput={(e) => onChangeQ(e)} 
                  min={1} 
                  max={20} 
                  step="1" 
                  value={selectedQ} 
                />
                <span> 20</span>
              </span>
            </h3>
            <h3>
              Итоговая стоимость: <b>{totalPrice/selectedQ} RUB x {selectedQ} мес. = {totalPrice} RUB</b>
            </h3>
            <hr />
          </>
        )}
        
        <h3><b>4 - </b>Выберите хост для VM: 			<button onClick={LoadHosts} id='updbtn'>Обновить</button></h3>
        {loadingHosts ? (
			<>
			  <div className='spinner'></div>
			</>
        ) : (
			<>
         <div id='hostslist'>
  {hosts.map((item, index) => {
    let className = 'glassy';
    
    if (item.avail === true) {
      className += ' ilist';
    } else {
      className += ' error';
    }
    
    if (item.avail === true && item.host_id === selectedHost) {
      className += ' selected';
    }
    
    return (
      <div 
        className={className}
        key={item.host_id}
        onClick={item.avail === true ? () => handleHostClick(item.host_id) : undefined}
      >
        <b>{item.name}</b> (10.8.0.{item.ip}) - {item.avail === true ? 'online' : 'offline'}
      </div>
    );
  })}	</div><hr/>
	{noHostsState ? (<span className='glassy error' id='noserveralert'><b>Извините, на данный момент нет доступных серверов😥</b></span>) : (<>        <h3>
          <button onClick={Continue} id='noserveralert'>▶️▶️ Перейти на страницу оплаты ▶️▶️</button>
        </h3></>)}

		  </>
        )}
      </div>
    </main>
  </>
);
}

export default Start;
