import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../components/Header.jsx';
import { useRoot } from "../context/RootContext.jsx";
import './Start.css';

function Start() {
  const { user, authLoading, openModal } = useRoot();
  const navigate = useNavigate();
  const [loading,setLoading] = useState(true);
  const [plansData,setPlansData] = useState(null);
  const [selectedPlan, setSelectedPlan] = useState([,]);
  const [selectedQ, setSelectedQ] = useState(1);
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
          setPlansData(response.data);
          console.log(response.data[0].plan_id, response.data[0].price);
          setSelectedPlan([response.data[0].plan_id, response.data[0].price]);
        } 
        else{
          openModal(response, true);
        }
      }
      else{
        openModal('Ошибка ' + xhr.status, true);
      }
    }
	},[]);
  function Continue(){
    let xhr = new XMLHttpRequest;
    let formData = new FormData();
    formData.append('plan_id',selectedPlan[0]);
    formData.append('q',selectedQ);
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
  const ChangePlan = (id) => {
    setSelectedPlan([id,plansData[id-1].price]);
  }
  const onChangeQ = (event) => {
    setSelectedQ(event.target.value);
  } 
  return (
    <>
      <Header />
      <main>
        <div>
			<h3>Какие доступны тарифы?</h3>
      {
        loading ? (
          <div className='spinner'></div>
        ) : (<>
          {plansData.map((item) => {
            return (<div className={item.plan_id == selectedPlan[0] ? 'glassy plan ilist selected' : 'glassy plan ilist'} id={'p' + item.plan_id} onClick={() => ChangePlan(item.plan_id)}><span><b>{item.name}</b></span><span><ul><li>Ядра процессора: {item.cpus}</li><li>Оперативная память: {item.ram} GB</li><li>Место на диске: {item.drive} GB</li></ul></span><span className='planprice'>{item.price} RUB/мес.</span></div>)
          })}
          <p>Выберите срок (в мес.): <input 
  type="number" 
  id="period" 
  min="1" 
  max="20" 
  value={selectedQ}
  onChange={onChangeQ}
  step='1'
/><button onClick={Continue}>Оплатить {selectedPlan[1]*selectedQ} RUB</button></p>
        </>)
      }
		</div>
      </main>
    </>
  );
}

export default Start;
