import { createContext, useContext, useState } from "react";

const ModalContext = createContext();

export function ModalProvider({ children }) {
    const [modal, setModal] = useState({
        visible: false,
        message: "",
        error: false,
		closing: false
    });

    const openmodal = (message, error = false) => {
        setModal({
            visible: true,
            message,
            error,
			closing: false
        });
    };

    const closemodal = () => {
        setModal(prev => ({ ...prev, closing: true }));
		setTimeout(() => {
			setModal(prev => ({ ...prev, visible: false, closing: false }));
		}, 200);
    };

    return (
        <ModalContext.Provider value={{ modal, openmodal, closemodal }}>
            {children}
        </ModalContext.Provider>
    );
}

export const useModal = () => useContext(ModalContext);
