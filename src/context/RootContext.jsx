import { createContext, useContext, useState, useEffect } from "react";
import { useLocation } from "react-router-dom";

const RootContext = createContext();

export function RootProvider({ children }) {
	const location = useLocation();
	const [user, setUser] = useState(null);
	const [authLoading, setAuthLoading] = useState(true);
	const openModal = (message, error = false) => {
        setModal({
            visible: true,
            message,
            error,
			closing: false
        });
    };
	const checkAuth = () => {
	  setAuthLoading(true);

	  const xhr = new XMLHttpRequest();
	  xhr.open('GET', 'https://anryb0.ru/sky/api/login_check.php');
	  xhr.withCredentials = true;
	  xhr.send();

	  xhr.onload = () => {
		if (xhr.status === 200) {
		  const response = JSON.parse(xhr.responseText);
		  setUser(response.authorized ? response.name : null);
		} else {
		  openmodal('Ошибка ' + xhr.status + ' при проверке авторизации', true);
		}
		setAuthLoading(false);
	  };
	};

	useEffect(() => {
		checkAuth();
	},[location.pathname]);
	
    const [modal, setModal] = useState({
        visible: false,
        message: "",
        error: false,
		closing: false
    });

    const closemodal = () => {
        setModal(prev => ({ ...prev, closing: true }));
		setTimeout(() => {
			setModal(prev => ({ ...prev, visible: false, closing: false }));
		}, 200);
    };

    return (
        <RootContext.Provider value={{ modal, openModal, closemodal,checkAuth, user, authLoading }}>
            {children}
        </RootContext.Provider>
    );
}

export const useRoot = () => useContext(RootContext);
//export { RootProvider, useRoot };
