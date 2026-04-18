import { Link } from 'react-router-dom';
import Header from '../components/Header.jsx';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import './Home.css';

function Home() {
  return (
    <>
      <Header />
      <main>
        <Swiper
          modules={[Navigation, Pagination, Autoplay]}
          spaceBetween={30}
          slidesPerView={1}
          navigation
          pagination={{ clickable: true }}
          className="info-swiper"
		  effect={'coverflow'} 
		  coverflowEffect={{
			rotate: 50,
			stretch: 0,
			depth: 100,
			modifier: 1,
			slideShadows: false, 
		  }}
        >
          <SwiperSlide>
			<div id='info-1' className='glassy info'>
            <h2>Что такое Sky?</h2>
			<hr />
			<br /><div id='p1'><div><h3><b>Sky - учебный прототип персонального облака.</b></h3><br /><b>Облако</b> представляет собой сеть удаленных серверов, предназначенную для хранения и обработки данных для других устройств и компьюте­ров. Наш проект относится к модели <b>IaaS (Infrastructure as a Service — Инфраструктура как услуга)</b>. IaaS предоставляет виртуальную инфраструктуру, включая виртуальные машины, хранилище данных и сетевые ресурсы. <br /><br />Все серверы работают на ОС <b>Ubuntu Server 24.04</b>.
			</div><img alt='серверная' title='серверная' src="/sky/c1.jpg" className='articleimg'/></div></div>
          </SwiperSlide>

          <SwiperSlide>
            <div id='info-2' className='glassy info'>
			<h2>Зачем это нужно?</h2>
			<hr />
			<br /><div id='p2'>
			<img alt='схема' title='схема' src="/sky/c2.png" className='articleimg'/>
			<div>
			<h3><b>Основная цель облака — предоставление доступа по требованию к вычислительным ресурсам и услугам через Интернет.</b></h3>
			<p>Наш прототип может быть использован для: </p>
			<ul>
			<li><b>Размещения сайтов, баз данных и ботов</b>: арендованный VDS позволяет управлять сайтами, базами данных, Telegram-ботами и другими проектами без ограничений</li>
			<li><b>Тестирования ПО</b>: виртуальная машина - это изолированная среда, на которой можно протестировать софт без риска нарушить работу основной системы</li>
			<li><b>Учёбы и экспериментов с Linux</b>: VDS дает полный доступ к системе, поэтому можно безопасно изучать возможности Ubuntu и других дистрибутивов Linux.</li>
			</ul>
			</div>
			</div>
			</div>
          </SwiperSlide>

          <SwiperSlide>
            <div id='info-3' className='glassy info'>
			<h2>Как это работает?</h2>
			<hr />
			<br /><div id='p3'>
			<div>
			<h3><b>Мы создаем персональное облако, поэтому в роли хостов используются не сервера из ЦОД, а локальные компьютеры.</b></h3>
			<p>В нашем случае у этих хостов нет выделенных внешних                                                                                                                                             IP, прямой доступ к ним из интернета не возможен.<br /><br />
			Для решения этой проблемы был арендован удаленный сервер, который работает как шлюз для сети, к которой подключены устройства клиентов нашего хостинга, арендуемые ими виртуальные машины и наши хосты. Сеть построена на базе <b>OpenVPN</b>.<br /><br /> Кроме того, на удаленном сервере работает и этот сайт, который предоставляет пользователям интерфейс для заказа услуг и управления ими.</p>
			</div>
			<img alt='схема' title='схема' src="/sky/c3.png" className='articleimg'/>
			</div>
			
			</div>
          </SwiperSlide>
		  
		  <SwiperSlide>
		  <div id='info-4' className='glassy info'>
		  <h2>Виртуализация</h2>
			<hr />
			
			<div id='p4'>
			<img alt='гипервизор' title='гипервизор' src="/sky/c4.png" className='articleimg'/>
			<p>Важным компонентом проекта является <b>виртуализация</b>, она отделяет вычислительные ресурсы от оборудования и позволяет создавать множество виртуальных сред на одной физической IT-инфраструктуре. Пользователь арендует <b>виртуальный сервер (VPS/VDS)</b> — это изолированный виртуальный аналог физического сервера с заданными лимитами ресурсов.
			Основой виртуальной среды является <b>гипервизор</b> — программа, которая создает виртуальные машины, распределяет между ними аппаратные ресурсы, а также обеспечивает их изоляцию друг от друга и от реального оборудования. Наш проект использует гипервизор <b>KVM</b>.</p>
			</div>
			</div>
		  </SwiperSlide>
        </Swiper>
		
		
		

        <div id='info-5' className='glassy info video-block video'>
          <h2>Как использовать?</h2>
          <hr />
          <video src='https://anryb0.ru/sky/guide.mp4' controls poster='sky.png'></video>
        </div>

        <p className='center'>
          <Link to='/start' id='try'>Арендовать VDS</Link>
        </p>
      </main>

    </>
  );
}

export default Home;