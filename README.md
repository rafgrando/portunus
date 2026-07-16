# Portunus 🗝️

[![Latest Stable Version](https://poser.pugx.org/rafgrando/portunus/v/stable)](https://packagist.org/packages/rafgrando/portunus)
[![License](https://poser.pugx.org/rafgrando/portunus/license)](https://packagist.org/packages/rafgrando/portunus)

**Portunus** is a modern PHP library designed for seamless integration with physical access control hardware. It currently supports TCP/IP communication with the **Nice / Linear-HCS MG3000 Gatekeeper Module (Módulo Guarita)**, a widely adopted industry standard for condominium and corporate security in Latin America.

---

## Why "Portunus"?

> In ancient Roman religion and mythology, **Portunus** was the god of keys, doors, gates, and cattle. He was traditionally depicted holding a key, guarding the crossings and entryways. 
>
> This library acts as a digital key, bridging modern PHP applications with physical entryways, gates, and security barriers.

---

## Features

* **TCP/IP Native Socket Connection:** Lightweight, fast, and robust socket-level communication with the MG3000.
* **Command & Frame Parsing:** Built-in abstraction for raw byte frames, saving you from dealing with low-level buffer manipulation manually.
* **PSR-4 Compliant:** Clean, modern, and fully compatible with Composer-based workflows.

---

## Installation

Install the package via [Composer](https://getcomposer.org/):

```bash
composer require rafgrando/portunus
```

## Contributing

Contributions are welcome! If you want to add support for other physical access control devices, relay boards, or serial (RS232/RS485) communication, feel free to open a Pull Request or start an Issue.


## License

Distributed under the MIT License. See LICENSE for more information. This ensures you are free to use this library in personal, open-source, or proprietary commercial products without licensing friction.

Developed with 💻 and ☕ by Rafael Grando.
