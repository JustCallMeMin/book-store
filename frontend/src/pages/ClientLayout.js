import React, { Component } from "react";
import Header from "../components/Header";
import HomePage from "./HomePage";

export default class ClientLayout extends Component {
    render() {
        return (
            <div>
                <Header />
                <HomePage />
            </div>
        );
    }
}
