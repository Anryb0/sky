import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx'
import './Control.css';
import { useRoot } from '../context/RootContext.jsx';

function Control(){
	const navigate = useNavigate();
	const { user, authLoading, openModal } = useRoot();
	const[loading,setLoading] = useState(true);
	const[maininfo,setMaininfo] = useState(null);
	const[resq,setResq] = useState(null);
	const[hostavail,setHostavail] = useState(false);
	const[avail,setAvail] = useState(false);
	if(!authLoading && !user){
		navigate('/');
	}
	
	useEffect(()=>{
		loadMainInfo();
	},[])
	
	function loadMainInfo(){
		setLoading(true);
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		if(!urlParams.get('i')){
			openModal('Некорректный URL', true);
		}
		else{
			let server_id = urlParams.get('i');
			let xhr = new XMLHttpRequest();
			let formData = new FormData();
			formData.append('server_id',server_id);
			formData.append('mode',0);
			xhr.open("POST", "https://anryb0.ru/sky/api/control.php", true);
			xhr.withCredentials = true;
			xhr.send(formData);
			xhr.onload = function(){
				if(xhr.status == 200){
					let response = JSON.parse(xhr.responseText);
					if(response.success){
						setMaininfo(response.maininfo);
						setHostavail(response.hostavail);
						setAvail(response.avail);
						setResq(response.resq);
						setLoading(false);
					}
					else{
						openModal(response.message, true);
					}
				}
				else{
					openModal('Ошибка ' + xhr.status + ' при получении данных о сервере', true);
				}
			}		
		}
	}
	function deleteServer(){
		
	}
	function startServer(){
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		if(!urlParams.get('i')){
			openModal('Некорректный URL', true);
			return;
		}
		let server_id = urlParams.get('i');
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('server_id',server_id);
		formData.append('mode',1);
		xhr.open("POST", "https://anryb0.ru/sky/api/control.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				openModal("Запрос отправлен");
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при отправке запроса', true);
			}
		}
	}
	function stopServer(){
		const queryString = window.location.search;
		const urlParams = new URLSearchParams(queryString);
		if(!urlParams.get('i')){
			openModal('Некорректный URL', true);
			return;
		}
		let server_id = urlParams.get('i');
		let xhr = new XMLHttpRequest();
		let formData = new FormData();
		formData.append('server_id',server_id);
		formData.append('mode',2);
		xhr.open("POST", "https://anryb0.ru/sky/api/control.php", true);
		xhr.withCredentials = true;
		xhr.send(formData);
		xhr.onload = function(){
			if(xhr.status == 200){
				openModal("Запрос отправлен");
			}
			else{
				openModal('Ошибка ' + xhr.status + ' при отправке запроса', true);
			}
		}
	}
	return (
		<>
			<Header />
			<main>
				{ loading ? (<div className='spinner'></div>) : 
					(<>
				<h2 className='fw'>server{maininfo.host + 1}/{maininfo.name}<button className="r" onClick={() => {loadMainInfo()}}>Обновить</button></h2>
							<hr />
							<div id='controlgrid'>
								<div className='glassy'>
									<h3><b>Характеристики</b></h3>
									<hr />
									{
										resq.map((item)=>{
											return (
												<p>{item.name} - <b>{item.q}</b></p>
											)
										})
									}
								</div>
								<div className='glassy'>
									<h3><b>Данные</b></h3>
									<hr />
									<p>Аренда истекает: <b>{maininfo.expires_at}</b></p>
									<p>IP: <b>10.8.0.{maininfo.ip}</b></p>
									<p>Root пароль: <button className='nm l' onClick={() => {openModal('Пароль скопирован в буфер обмена');
									navigator.clipboard.writeText(maininfo.password.replace(/^\uFEFF/g, '').replace(/[^\x20-\x7E]/g, ''));
									}}>Копировать</button></p>
								</div>
								<div className='glassy'>
								<h3><b>Управление</b></h3>
								<hr />
								{
									
									hostavail ? (<>
										<p>Статус: <b>{maininfo.status}</b></p>
										{
											avail ? (<><button onClick={() => startServer()}>Перезагрузить</button><button onClick={() => stopServer()}>Выключить</button></>) : (
												<button onClick={() => startServer()}>Включить</button>
											)
										}
										<button className='error' onClick = {()=>{
											openModal(<span>
												Возврат средств или восстановление сервера не предусмотрено.
											<button className='error'>Все равно удалить</button></span>, true);
										}}>Удалить</button>
									</>) : (
										<>
										<p className='redf'>Извините, хост недоступен. Управление VM невозможно.</p>
										</>
									)
								}
								</div>
							</div>
					</>)
				}
			</main>
		</>
	)
}

export default Control;