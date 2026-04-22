import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx'
import './Profile.css';
import { useRoot } from '../context/RootContext.jsx';

function Profile(){
	const navigate = useNavigate();
	const { user, authLoading, openModal } = useRoot();
	const[loading,setLoading] = useState(true);
	const[response,setResponse] = useState(null);
	const[loadingServers,setLoadingServers] = useState(true);
	const[servers,setServers] = useState(null);
	const[greeting,setGreeting] = useState(null);
	const[checkedHosts,setCheckedHosts] = useState(false);
	
	function logout(){
		let xhr = new XMLHttpRequest();
		xhr.open("GET", "https://anryb0.ru/sky/api/logout.php", true);
		xhr.withCredentials = true;
		xhr.send(null);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				navigate('/');
			}
			else{
				openmodal('Ошибка ' + xhr.status + ' при попытке выхода', true);
			}
		};
	}
	function sendNewLink(){
		let xhr = new XMLHttpRequest();
		xhr.open('GET','https://anryb0.ru/sky/api/makenewlink.php');
		xhr.withCredentials = true;
		xhr.send();
		xhr.onload = function(){
			let response = JSON.parse(xhr.responseText);
			if(response.success){
				openModal('Письмо успешно отправлено');
			}
			else{
				openModal(response.message,true);
			}
		} 
	}
	function loadBasicInfo(){
		setLoading(true);
		setGreeting(['Добро пожаловать','Привет','С возвращением'][Math.floor(Math.random() * (3))])
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('mode',0);
		xhr.open("POST", "https://anryb0.ru/sky/api/profile.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				setResponse(response);
				setLoading(false);
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при получении данных профиля', true);
			}
		};
	}
	useEffect(()=>{
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		if(urlParams.get('createdserver')){
			displayServerAlert(parseInt(urlParams.get('createdserver')))
		}
		loadBasicInfo();
		loadServers();
	},[])
	function displayServerAlert(serverId){
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('serverId',serverId);
		formData.append('mode',2);
		xhr.open("POST", "https://anryb0.ru/sky/api/profile.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				let data = response.data;
				openModal(
				  <span>
					VM {data.name} успешно создана. <button onClick={() => {window.location.href ="https://anryb0.ru/sky/control?id="+serverId}}>Перейти к управлению</button>
				  </span>,
				  false
				)
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при получении данных о сервере', true);
			}
		}
	}
	function loadServers(){
		if(!authLoading && !user){
			navigate('/register');
		}
		setLoadingServers(true);
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('mode',1);
		xhr.open("POST", "https://anryb0.ru/sky/api/profile.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				setServers(response.servers);
				setLoadingServers(false);
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при получении списка серверов', true);
			}
		};
	}
	function addUserConfig(){
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('mode',3);
		xhr.open("POST", "https://anryb0.ru/sky/api/profile.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				if(response.success){
					openModal('Конфигурация создана');
					loadBasicInfo();
				}
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при создании новой конфигурации', true);
			}
		}
	}
	function checkHosts(){
		let xhr = new XMLHttpRequest();
		xhr.open('GET', 'https://anryb0.ru/sky/api/checkserveravail.php');
		xhr.withCredentials = true;
		xhr.send();
		xhr.onload = function(){
			if(xhr.status == 200){
				let response = JSON.parse(xhr.responseText);
				if(response.success){
					setCheckedHosts(response.hosts_avail);
					let xhr2 = new XMLHttpRequest();
					let formData = new FormData();
					formData.append('hostsavail',response.hosts_avail);
					xhr2.open('POST', 'https://anryb0.ru/sky/api/allvmcheck.php');
					xhr2.withCredentials = true;
					xhr2.send(formData);
					xhr2.onload = function(){
						loadServers();
					}					
				}
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при обновлении статусов хостов', true);
			}
		}
		
	}
	function checkAll(){
		setLoadingServers(true);
		checkHosts();
	}
	if(!authLoading && !user){
		navigate('/register');
	}
	return (
		<>
			<Header nonbut='true'/>
			<main>
			{loading ? (<div className='spinner center'></div>) : ( 
				<div id='maininfo'>
					<h3 id='topheader'><b>{greeting}, {user}</b><button onClick={() => {loadBasicInfo()}}>Обновить</button></h3>
						{ response.confirmed ? (<div className='glassy t'><span className='green'>Подтвержденный аккаунт</span><span className='right'>Ваша почта: {response.email}</span></div>) : (<div className='glassy tf'><span>Ваша почта: <b>{response.email} </b>
						</span><span className='right'><span className='redf'>  Ваш аккаунт не подтвержден. Проверьте почту</span><button onClick={sendNewLink}>Отправить еще ссылку</button></span></div>)
						}
						{
							response.ip ? (<div className='glassy t'><span className='green'>Сеть настроена</span><span className='right'><button onClick={() => {window.location.href ="https://anryb0.ru/sky/api/downloaduserconfig.php"}}>Скачать VPN конфигурацию</button>
							<button onClick={() => {addUserConfig()}}>Пересоздать</button>
							<button onClick={() => {window.location.href ="https://anryb0.ru/sky/api/downloadovpn.php"}}>Скачать OpenVPN Connect</button></span></div>) :
							(<div className='glassy t'>У вас пока нет VPN конфигураций  <span className='right'><button onClick={() => {addUserConfig()}}>Создать</button></span></div>)
						}
						</div>)
			}
			<hr />
			<h3><b>Ваши серверы</b><button onClick={() => {window.location.href ="/sky/start"}} className='r'>+</button><button onClick={() => {checkAll()}} className='r'>Ping</button><button onClick={() => {loadServers()}} className='r'>Обновить</button></h3>
			{
				loadingServers ? (<div className='spinner'></div>) : servers.length > 0 ? (<>
					<div id='stop'><span>Название</span><span>IP</span><span>Статус</span><span>Хост</span></div>
					<hr />
					{servers.map((item) => {
						let hostClass = ""; 
						if (checkedHosts) {
							const hostData = checkedHosts.find(h => h.name === item.hname);
							hostClass = hostData.avail ? "green" : "redf";
						}
						return (
							<Link className='glassy ilist s e' to={'../control?i=' + item.server_id} key={item.server_id}>
								<b>{item.name}</b>
								<span>10.8.0.{item.ip}</span>
								
								<span>
									{item.status === 'Ждёт оплаты' ? (
										<button onClick={(e) => { e.preventDefault(); window.location.href = item.link; }}>
											Ждёт оплаты
										</button>
									) : item.status === 'Устанавливается' ? (
										<>
											<div className='spinner sm'></div>
											<span className='grey'> Устанавливается</span>
										</>
									) : item.status === 'Работает' ? (
										<span className='green'>Работает</span>
									) : item.status === "Выключен" ? (
										<span className='redf'>Выключен</span>
									) : (
										item.status
									)}
								</span>
								<span className={hostClass}>{item.hname}</span>
							</Link>
						);
					})}
					</> 
				) : (<div className='t'>У вас пока нет серверов<span className='right'></span></div>)
			}
			<br />
			<hr />	
			<div className='w'><button onClick={logout} className='error'>Выйти из аккаунта</button></div>
			</main>
		</>
	)
}

export default Profile;