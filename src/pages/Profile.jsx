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
	if(!authLoading && !user){
		navigate('/');
	}
	return (
		<>
			<Header nonbut='true'/>
			<main>
			{loading ? (<div className='spinner center'></div>) : ( 
				<div id='maininfo'>
					<h3 id='topheader'><b>{greeting}, {user}</b><button onClick={() => {loadBasicInfo()}}>Обновить</button></h3>
						{ response.confirmed ? (<div className='glassy t'>✔️ Подтвержденный аккаунт<span className='right'>Ваша почта: <b>{response.email}</b></span></div>) : (<div className='glassy error'>Ваша почта: <b>{response.email}</b>
						<span className='right'>❌ Ваш аккаунт не подтвержден. Проверьте почту</span><button className='green' onClick={sendNewLink}>Отправить еще ссылку</button></div>)
						}
						{
							response.ip ? (<div className='glassy t'>✔️ Сеть настроена<span className='right'><button onClick={() => {window.location.href ="https://anryb0.ru/sky/api/downloaduserconfig.php"}}>Скачать VPN конфигурацию</button>
							<button onClick={() => {addUserConfig()}}>Пересоздать</button>
							<button onClick={() => {window.location.href ="https://anryb0.ru/sky/api/downloadovpn.php"}}>Скачать OpenVPN Connect</button></span></div>) :
							(<div className='glassy t'>❌ У вас пока нет VPN конфигураций  <span className='right'><button onClick={() => {addUserConfig()}}>Создать</button></span></div>)
						}
						</div>)
			}
			<hr />
			<h3><b>Ваши сервера</b><button onClick={() => {window.location.href ="https://anryb0.ru/sky/start"}} className='r'>+</button><button onClick={() => {loadServers()}} className='r'>Обновить</button></h3>
			{
				loadingServers ? (<div className='spinner'></div>) : servers.length > 0 ? (<>
					<div id='stop'><span>Название</span><span>IP</span><span>Статус</span><span>ОС</span></div>
					<hr />
					{servers.map((item)=> {
						return (<div className='glassy ilist s'><b>{item.name}</b><span>10.8.0.{item.ip}</span><span>{item.status == 'Ждёт оплаты' ? (<button onClick={() => {window.location.href=item.link}}>Ждёт оплаты</button>) : (item.status)}</span><span>{item.oname}</span></div>)
					})}</> 
				) : (<div className='t'>❌ У вас пока нет серверов<span className='right'></span></div>)
			}
			<br />
			<hr />	
			<div className='w'><button onClick={logout} className='error'>Выйти из аккаунта</button></div>
			</main>
		</>
	)
}

export default Profile;